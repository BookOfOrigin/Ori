<?php
namespace Origin\DB;

use \DateTime;
use \ReflectionClass;

trait DBTools {
	public function ColumnMap(){
		$this->PopulateColumns();
		return $this->columns;
	}
	
	public function ArrayToObject(array $array){
		foreach($array as $key => $value){
			if(isset($this->ColumnMap()[$key]) && $value !== null){
				switch($this->ColumnMap()[$key]){
					case 'Origin\Utilities\Bucket\Common::Date':
						if(is_numeric($value)){
							$this->$key((new DateTime()));
							$this->$key()->setTimestamp($value);
						} else {
							$this->$key((new DateTime($value)));
						}
						break;
					case 'Origin\Utilities\Bucket\Common::Boolean':
						$this->$key(($value === '1' || $value === 1) ? true : false);
						break;
					case 'Origin\Utilities\Bucket\Common::Hash':
						$result = json_decode($value, true);
						if($result !== null && is_array($result)){
							$this->$key($result);
						} else {
							$result = array();
							$prepare = explode("\n", $value);
							foreach($prepare as $id => $row){
								$result[str_replace('\r', '', str_replace("\r", '', $id))] = str_replace('\r', '', str_replace("\r", '', $row));
							}
							
							$this->$key($result);
						}
						break;
					case 'Origin\Utilities\Bucket\Common::Float':
						$this->$key((float) ($value !== '' ? $value : null));
						break;
					default:
						$this->$key($value !== '' ? $value : null);
						break;
				}
			}
		}
		
		return true;
	}
	
	protected $columns;
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
}