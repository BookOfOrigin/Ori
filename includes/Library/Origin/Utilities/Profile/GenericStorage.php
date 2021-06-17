<?php
namespace Origin\Utilities\Profile;

use \Origin\Log\Log;
use \Origin\Utilities\Types\CustomStorage;

class GenericStorage implements \Origin\Utilities\Profile\Interfaces\iStorage {
	public function Shutdown(CustomStorage $timings){
		$log = Log::Get('profile');
		foreach($timings as $timing){
			$log->Warning($timing->Format());
		}
	}
}