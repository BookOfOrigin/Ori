<?php
namespace Origin\DB;

class Parameters extends \ArrayObject {
	const EQUAL = '=';
	const NOT_EQUAL = '!=';
	const LT = '<';
	const GT = '>';
	const LET = '<=';
	const GET = '>=';
	const LIKE = 'like';
	public function __construct(){
		$array = func_get_args();
		foreach($array as $parameter){
			if(is_array($parameter)){
				$this->Add($parameter);
			}
		}
		
		return $this;
	}
	
	public function Add(array $array = array()){
		$this->append((new Parameter($array)));
	}
	
	public function ByColumnName($name){
		foreach ($this as $parameter){
			if($parameter->Column() === $name){
				return $parameter;
			}
		}
		
		return null;
	}
}