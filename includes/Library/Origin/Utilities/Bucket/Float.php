<?php
namespace Origin\Utilities\Bucket;

use \Exception;

trait Float {
	public function Float($value = null){
		if(($value !== null) && (!is_float($value))){
			throw new Exception(sprint_f('Invalid value specified for type %s.', __FUNCTION__));
		}
		
		return $this->Bucket(null, $value);
	}
}
