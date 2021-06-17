<?php
namespace Origin\Utilities\Profile;

use \Origin\Utilities\Bucket\Bucket;
use \Origin\Utilities\Bucket\Common;

class GenericPair implements \Origin\Utilities\Profile\Interfaces\iTimingPair {
	use Bucket, Common {
		Number as Start;
		Number as Stop;
	}
	
	public function __construct(){
		$this->things['Start'] = microtime(true);
	}
	
	public function jsonSerialize(){
		return $this->things;
	}
	
	public function Difference(){
		if($this->Stop() === null){
			$this->Stop(microtime(true));
		}
		
		return round((($this->Stop() - $this->Start()) * 1000), 4);
	}
}