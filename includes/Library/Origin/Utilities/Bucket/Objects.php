<?php
namespace Origin\Utilities\Bucket;

use \Exception;

trait Objects {
	public function Objects($value = null){
		return $this->Bucket(null, $value);
	}
}