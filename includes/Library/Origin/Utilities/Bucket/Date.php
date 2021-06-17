<?php
namespace Origin\Utilities\Bucket;

use \Exception;

trait Date {
	public function Date(\DateTime $value = null){
		return $this->Bucket(null, $value);
	}
}