<?php
namespace Origin\Utilities\Types;

class CustomStorage extends \ArrayObject implements \JsonSerializable {
	/*
	* Finds an object which has the ID passed and returns said object.
	*/
	public function ByID($id = null){
		foreach($this as $item){
			if($item->ID() === $id){
				return $item;
			}
		}
	}
	
	public function MultiPopulate(array $array){
		foreach($array as $key => $value){
			if(is_array($value)) {
				$object = new CustomStorage();
				$object->MultiPopulate($value);
				$this->offsetSet($key, $object);
			} else {
				$this->offsetSet($key, $value);
			}
		}
		
		return $this;
	}
	
	public function Deconstruct(){
		$return = array();
		foreach($this as $key => $value){
			if($value instanceof CustomStorage){
				$return[$key] = $value->Deconstruct();
			} else {
				$return[$key] = $value;
			}
		}
		
		return $return;
	}
	
	public function jsonSerialize(){
		$return = $this->getArrayCopy();
		if(isset($return['AuthenticationTokens'])){
			unset($return['AuthenticationTokens']);
		}
		
		return $return;
	}
	
	public function __call($name, $arguments){
		if($this->count() === 1){
			if(method_exists($this->offsetGet(0), $name)){
				return call_user_func_array(array($this->offsetGet(0), $name), $arguments);
			} else {
				throw new Exception(sprintf('Tried to call nonexistant function %s in object %s', $name, get_class($this->offsetGet(0))));	
			}
		} else {
			throw new Exception(sprintf('Tried to call method %s, but method object is a CustomStorage with multiple entries.', $name));
		}
	}
}