<?php
namespace Origin\DB;

use \Origin\DB\Parameters;
use \Origin\Utilities\Settings;
use \Origin\Utilities\Utilities;
use \Origin\Utilities\Types\CustomStorage;
use \Origin\Utilities\Types\Exception;

/*
* This is here because I really hate duplicating code for the same purpose.
* In general Query() should not be called directly use QueryObjects instead.
*/
class Selector extends \Origin\Utilities\Types\Singleton {
	private $database;
	public function __construct(){
		$this->database = DB::Get(Settings::Get()->Value(['origin', 'default_database']));
	}
	
	public function Query($table, Parameters $parameters = null, array $columns = array(), $joins = null, $order_by = 'ID'){
		$where = $this->GenerateWhere($parameters);
		//\Origin\Log\Log::Get('sql')->Log(sprintf(static::$generic_select_where, implode(', ', $columns), $table, $joins, $where['sql']), null, \Origin\Log\Log::SEVERITY_WARNING);
		return $this->database->Query(sprintf(static::$generic_select_where, $this->PrepareColumns($columns), $table, $joins, $where['sql'], $order_by, $where['limit']), $where['binds']);
	}
	
	/*
	* Returns an array of the specified object based on query parameters.
	*/
	public function FetchObjects(DatabaseAssistant $object, Parameters $parameters = null, $children = false, $cache = false){
		if($cache === true && $this->FetchCache($object, $parameters, $children) !== null){
			return $this->FetchCache($object, $parameters, $children);
		}
		
		$return = new CustomStorage();
		$results = $this->Query($object->Table(), $parameters, $object->Columns(), $object->Joins(), $object->OrderBy());
		foreach($results as $data){
			$class = get_class($object);
			$tmp = new $class;
			if($tmp->ArrayToObject($data)){
				$return->append($tmp);
			}	
		}
		
		if($children === true){
			$this->FetchChildren($return);
		}
		
		if($cache === true){
			$this->CacheResults($object, $parameters, $children, $return);
		}
		
		return $return;
	}
	
	public function FetchChildren(CustomStorage $parents = null, DatabaseAssistant $parent = null, $reset_cache = true){
		if($parent !== null){
			$parents = new CustomStorage([$parent]);
		}
		
		if($parents->offsetExists(0)){
			$children = $parents->offsetGet(0)->Children();
			foreach($children as $child){
				$storage = $child->ParentStorage();
				foreach($parents as $parent){
					$parent->$storage($this->GetParentResults($parents, $parent, $child));
				}
			}

			if($reset_cache === true){
				$this->child_results = array();
			}
		}
		
		return $parents;
	}
	
	protected $child_results = array();
	protected function GetParentResults(CustomStorage $parents, DatabaseAssistant $parent, Child $child){
		$parent_column = $child->ParentColumn();
		$child_column = $child->ChildColumn();
		if(!isset($this->child_results[get_class($child->Child())])){
			$array = array();
			foreach($parents as $ps){
				$tmp = $ps->$parent_column();
				if(is_array($tmp)){
					$array = array_merge($array, $tmp);
				} else {
					$array[] = $tmp;
				}
			}
			
			$parameters = (new Parameters(['Column' => $child_column, 'Value' => $array]));
			if($child->Parameters() !== null){
				foreach($child->Parameters() as $parameter){
					$parameters->append($parameter);	
				}
			}
			
			$this->child_results[get_class($child->Child())] = $this->FetchObjects($child->Child(), $parameters, true);
		}
		
		$results = new CustomStorage();
		foreach($this->child_results[get_class($child->Child())] as $result){
			$parent_value = $parent->$parent_column();
			if(is_array($parent_value)){
				if(in_array($result->$child_column(), $parent_value)){
					$results->append($result);
				}
			} else {
				if($parent_value === $result->$child_column()){
					$results->append($result);
				}
			}
		}
		
		return $results;
	}
	
	private $cached_results = array();
	protected function FetchCache(DatabaseAssistant $object, Parameters $parameters = null, $children = false){
		if(isset($this->cached_results[$this->GenerateDigest($object, $parameters, $children)])){
			return $this->cached_results[$this->GenerateDigest($object, $parameters, $children)];
		}
	}
	
	protected function CacheResults(DatabaseAssistant $object, Parameters $parameters = null, $children = false, CustomStorage $return){
		$this->cached_results[$this->GenerateDigest($object, $parameters, $children)] = $return;
	}
	
	protected function GenerateDigest(DatabaseAssistant $object, Parameters $parameters = null, $children = false){
		return sha1(json_encode(array('object' => get_class($object), 'parameters' => $parameters, 'children' => $children)));
	}

	public function GenerateWhere(Parameters $parameters = null, $start = 101){
		if($parameters === null){
			$parameters = new Parameters();
		}
		
		$total_parameters = 1;
		$delete_statement = true;
		$where = array('sql' => '', 'binds' => array(), 'limit' => '');
		$iterator = $parameters->getIterator();
		foreach($iterator as $key => $parameter){
			// Throw exception if any of the parameters are not Parameter objects.
			if(!($parameter instanceof Parameter)){
				throw new \Exception('All parameters must be an instance of Parameter');
			}
			
			if($parameter->LimitStart() !== null){
				$where['binds']['limit_start'] = (int) $parameter->LimitStart();
				if($parameter->LimitCount() !== null){
					$where['binds']['limit_count'] = (int) $parameter->LimitCount();
					$where['limit'] = static::$limit_count_sql;
				} else {
					$where['limit'] = static::$limit_sql;
				}
			}
		}
		
		if($delete_statement === true){
			$parameters->Add(['Column' => 'q.Deleted', 'Value' => false]);
		}
		
		$sub_parameter_start = $start + 1;
		foreach($parameters as $parameter){
			if(($parameter->Column() !== null && ($parameter->Value() !== null || $parameter->Nullable() === true)) || (count($parameter->SubParameters()) > 0)){
				if(count($parameter->SubParameters()) > 0){
					$sub_where = $this->GenerateWhere($parameter->SubParameters(), $sub_parameter_start);
					$where['sql'] .= ($where['sql'] !== '' ? ' '.$parameter->Conjunction().' ' : '');
					$where['sql'] .= sprintf(static::$generic_subwhere, $sub_where['sql']);
					$where['binds'] = array_merge($where['binds'], $sub_where['binds']);
					$sub_parameter_start++;
				} else {
					// Cleanup.
					if($parameter->Value() instanceof \DateTime){
						$parameter->Value($parameter->Value()->format('Y-m-d H:i:s'));
					} elseif(is_bool($parameter->Value())){
						$parameter->Value(($parameter->Value() ? 1 : 0));
					} 

					// Prepare Statements.
					if(is_array($parameter->Value()) && count($parameter->Value()) > 0){
						$count = 0;
						$where['sql'] .= ($where['sql'] !== '' ? ' '.$parameter->Conjunction().' (' : ' ( ');
						foreach($parameter->Value() as $value){
							$bind_key = sprintf(':parameter%s%04d', $start, $total_parameters++);
							$where['sql'] .= ($where['sql'] !== '' && $count !== 0 ? ' '.$parameter->MultiConjunction().' ' : '');
							$where['sql'] .= $this->StatementGenerator($bind_key, $parameter->Column(), $parameter->Comparitor());
							$where['binds'][$bind_key] = $value;
							$count++;
						}
						$where['sql'] .= ')';
					} elseif(is_array($parameter->Value())){
						$where['sql'] .= ($where['sql'] !== '' ? ' '.$parameter->Conjunction().' (' : ' (');
						$bind_key = sprintf(':parameter%s%04d', $start, $total_parameters++);
						$where['sql'] .= sprintf('%s IN (%s)', $parameter->Column(), $bind_key);
						$where['binds'][$bind_key] = 'THIS_VALUE_IS_REALLY_REALLY_AWESOME_ENTROPY_PIZZA_TIME_SPACE_CONTINUUM';
						$where['sql'] .= ')';
					} else {
						$where['sql'] .= ($where['sql'] !== '' ? ' '.$parameter->Conjunction().' ' : '');
						if($parameter->Nullable() && $parameter->Value() === null){
							if($parameter->Comparitor() === '='){
								$where['sql'] .= $parameter->Column().' IS NULL';
							} else {
								$where['sql'] .= $parameter->Column().' IS NOT NULL';
							}
						} else {
							$bind_key = sprintf(':parameter%s%04d', $start, $total_parameters++);
							$where['sql'] .= $this->StatementGenerator($bind_key, $parameter->Column(), $parameter->Comparitor());
							$where['binds'][$bind_key] = $parameter->Value();
						}
					}
				}
			}
		}
		
		return $where;
	}
	
	public function PreviewGenerator(Parameters $parameters = null, $start = 101){
		$result = $this->GenerateWhere($parameters, $start);
		\Origin\Log\Log::Get()->Warning($result);
		$sql = $result['sql'];
		foreach($result['binds'] as $key => $value){
			if(is_numeric($value)){
				$sql = str_replace($key, $value, $sql);
			} else {
				$sql = str_replace($key, "'".$value."'", $sql);
			}
		}
		
		return $sql;
	}
	
	protected function StatementGenerator($key, $column, $comparitor){
		return $column.' '.$comparitor.' '.$key;
	}
	
	protected function PrepareColumns(array $columns = array()){
		$return = '';
		foreach($columns as $key => $value){
			if(count($columns) === ($key + 1)){
				$return .= ' q.'.$value;
			} else {
				$return .= ' q.'.$value.', ';
			}
		}
		
		return $return;
	}
	
	protected static $generic_select_where = <<<'SQL'
SELECT %s FROM %s q %s WHERE %s ORDER BY %s %s
SQL;
	
	protected static $generic_subwhere = <<<'SQL'
(%s)
SQL;
	
	protected static $limit_sql = <<<'SQL'
 LIMIT :limit_start
SQL;
	
	protected static $limit_count_sql = <<<'SQL'
 LIMIT :limit_start, :limit_count
SQL;
}