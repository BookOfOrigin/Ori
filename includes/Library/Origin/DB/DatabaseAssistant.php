<?php
namespace Origin\DB;

use \DateTime;
use \ArrayObject;
use \ReflectionClass;
use \Origin\Utilities\Utilities;
use \Origin\Utilities\Settings;

abstract class DatabaseAssistant implements \JsonSerializable {
	protected $database;
	protected $columns;
	protected $joins;
	protected $table;
	
	abstract public function ID($value = null);
	abstract public function Deleted($value = null);
	abstract public function Genesis(DateTime $value = null);
	abstract public function Mutation(DateTime $value = null);
	
	public function __construct(){
		$this->database = DB::Get(Settings::Get()->Value(['origin', 'default_database']));
	}
	
	public function jsonSerialize() {
		$return = $this->things;
		if(isset($return['ClassSpace'])){
			unset($return['ClassSpace']);
		}
		
		return $this->things;
	}
	
	public function Table(){
		if($this->table === null){
			$reflection = new \ReflectionClass($this);
			$this->table = $reflection->getShortName();
		}
		
		return $this->table;
	}
	
	public function Columns(){
		$this->PopulateColumns();
		return array_keys($this->columns);
	}
	
	public function ColumnMap(){
		$this->PopulateColumns();
		return $this->columns;
	}
	
	public function Children(){
		return array();
	}
	
	protected $order_by = 'ID';
	public function OrderBy($value = null){
		if($value !== null){
			$this->order_by = $value;
		}
		
		return $this->order_by;
	}
	
	protected $order_by_variables = array();
	public function OrderByVariables(array $variables = array()){
		if(!empty($variables)){
			$this->order_by_variables = $variables;
		}
		
		return $this->order_by_variables;
	}
	
	public function NullColumns(array $columns){
		foreach($columns as $function){
			if(method_exists($this, $function)){
				$this->things[$function] = null;
			}
		}
		
		return true;
	}
	
	protected function PopulateColumns(){
		if($this->columns === null){
			$this->columns = $this->GetTraits((new \ReflectionClass($this)));
			
			// Remove objects.
			foreach($this->columns as $name => $column){
				if(strpos($column, 'Objects') !== false){
					unset($this->columns[$name]);
				}
			}
		}
	}
	
	private function GetTraits(ReflectionClass $class){
		$columns = array();
		$class_columns = $class->getTraitAliases();
		if(!empty($class_columns)){
			$columns = array_merge($columns, $class_columns);
		}
		
		if($class->getParentClass() !== false){
			$columns = array_merge($columns, $this->GetTraits($class->getParentClass()));
		}
		
		return $columns;
	}
	
	public function FetchChildren(){
		Selector::Get()->FetchChildren(null, $this);
		return true;
	}
	
	public function Joins(){
		return $this->joins;
	}
	
	public function Reset(array $columns = array()){
		foreach($columns as $column){
			$this->things[$column] = null;
		}
		
		return true;
	}
	
	public function Update(array $columns = array()){
		if(empty($columns)){
			$columns = $this->ColumnMap();
		}
		
		if(!empty($columns) && $this->ID() !== null){
			$parameters = array();
			foreach($columns as $name => $type){
				if(is_numeric($name)){
					$name = $type;
				}
				
				if(isset($this->things[$name])){
					$parameters[$name] = $this->$name();
					if($this->$name() instanceof DateTime){
						$parameters[$name] = $this->$name()->format(DB::DEFAULT_DATE_FORMAT);
					}

					if($type === 'Origin\Utilities\Bucket\Common::Binary'){
						$parameters[$name] = (binary) $parameters[$name];
					}

					if($type === 'Origin\Utilities\Bucket\Common::Boolean'){
						if($parameters[$name] === true){
							$parameters[$name] = 1;
						} elseif($parameters[$name] === false){
							$parameters[$name] = 0;
						} else {
							$parameters[$name] = null;
						}
					}
				}
			}

			$this->database->Update($this->Table(), $parameters, 'ID = :id', array(':id' => $this->ID()));
			return true;
		}
		
		return false;
	}
	
	public function Insert(array $columns = array(), $ignore = false){
		if(empty($columns)){
			$columns = $this->ColumnMap();
		}
		
		if(!empty($columns) && $this->ID() === null){
			$parameters = array();
			foreach($columns as $name => $type){
				if($this->$name() !== null){
					if($type === 'Origin\Utilities\Bucket\Common::Binary'){
						$parameters[$name] = (binary) $this->$name();
					} else {
						$parameters[$name] = $this->$name();
					}
				}
			}
			
			$this->database->Insert($this->Table(), $parameters, DB::DEFAULT_DATE_FORMAT, $ignore);
			$this->ID($this->database->LastID());
			if($this->Genesis() === null){
				$this->Genesis((new DateTime()));
			}
			
			return true;
		}
		
		return false;
	}
	
	public function Delete(){
		$this->Deleted(true);
		return $this->Update(['Deleted']);
	}
	
	public function Remove(){
		$this->database->Remove($this->Table(), 'ID = :id', array(':id' => $this->ID()));
		return true;
	}
	
	public function Query(Parameters $parameters = null, array $columns = array(), $joins = null, $first = false, $children = false){
		if(empty($columns)){
			$columns = $this->Columns();
		}
		
		if(empty($joins)){
			$joins = $this->Joins();
		}
		
		$results = Selector::Get()->Query($this->Table(), $parameters, $columns, $joins);
		if((count($results) === 1 && isset($results[0])) || ($first === true && isset($results[0]))){
			if($children === true){
				$this->FetchChildren();	
			}
			
			return $results[0];
		}
		
		return null;
	}
	
	public function QueryObject(Parameters $parameters = null, $children = false, $first = false){
		$result = $this->Query($parameters, [], null, $first, $children);
		if($result !== null){
			return $this->ArrayToObject($result);
		}
		
		return false;
	}
	
	public function QueryID($id = null, $deleted = false){
		$result = $this->Query((new Parameters(['Column' => 'ID', 'Value' => $id], ['Column' => 'Deleted', 'Value' => $deleted])));
		if($result !== null){
			return $this->ArrayToObject($result);
		}
		
		return false;
	}
	
	public function ArrayToObject(array $array){
		foreach($array as $key => $value){
			if(isset($this->ColumnMap()[$key]) && $value !== null){
				switch($this->ColumnMap()[$key]){
					case 'Origin\Utilities\Bucket\Common::Date':
						$this->$key((new DateTime($value)));
						break;
					case 'Origin\Utilities\Bucket\Common::Boolean':
						$this->$key(($value === '1' || $value === 1) ? true : false);
						break;
					case 'Origin\Utilities\Bucket\Common::Hash':
						$this->$key(json_decode($value, true) ?? null);
						break;
					case 'Origin\Utilities\Bucket\Common::Decimal':
						$this->$key((float) $value);
						break;
					case 'Origin\Utilities\Bucket\Common::Binary':
						$this->$key((binary) $value);
						break;
					default:
						$this->$key($value);
						break;
				}
			}
		}
		
		return true;
	}
	
	public function PopulateFromSource(array $array){
		foreach($array as $key => $value){
			if(isset($this->ColumnMap()[$key]) && $value !== null){
				$this->$key($value);
			}
		}
		
		return true;
	}
}