<?php
namespace Origin\Utilities\Bucket;

trait Bucket {
	protected $things = array();
	protected function Bucket($key = null, $value = null){
		if($key === null){
			$key = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
		}
		if($value !== null){
			$this->things[$key] = $value;
		}
		return (isset($this->things[$key]) ? $this->things[$key] : null);
	}
	
	public function Reset(array $key = array()){
		foreach($key as $k){
			if(isset($this->things[$k])){
				unset($this->things[$k]);
			}
		}
	}
}
