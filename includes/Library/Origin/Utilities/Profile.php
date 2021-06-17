<?php
namespace Origin\Utilities;

use \Origin\Utilities\Bucket\Bucket;
use \Origin\Utilities\Bucket\Common;
use \Origin\Utilities\Types\CommonStorage;

class ProfileStorage {
	use Bucket, Common {
		String as Name;
		Number as Start;
		Number as Stop;
	}
	
	public function __construct($name){
		$this->Name($name);
		$this->Start(microtime(true));
	}
	
	public function Difference(){
		return round((($this->Stop() - $this->Start()) * 1000), 4);
	}
}

class Profile extends \Origin\Utilities\Types\Singleton {
	private $log;
	private $storage = array();
	public function __construct(){
		$this->log = \Origin\Log\Log::Get('profile');
	}
	
	public function __destruct() {
		$this->End();
	}
	
	public function Start($name){
		if(!isset($this->storage[$name])){
			$this->storage[$name] = array();
		}
		
		array_unshift($this->storage[$name], (new ProfileStorage($name)));
	}
	
	public function Stop($name){
		if(!isset($this->storage[$name])){
			return;
		}
		
		foreach($this->storage[$name] as $profile){
			if($profile->Stop() === null){
				$profile->Stop(microtime(true));
				break;
			}
		}
	}
	
	public function End($name = null){
		if($name === null){
			foreach($this->storage as $name => $object){
				$this->End($name);
			}
			
			return;
		}
		
		foreach($this->storage[$name] as  $id => $object){
			if($object->Stop() === null){
				$this->Stop($name);
			}
		}
		
		if(count($this->storage[$name]) === 1){
			foreach($this->storage[$name] as $id => $object){
				$this->log->Warning($name, sprintf('Total time for %s: %s', $object->Name(), $object->Difference() . ' ms'));
			}
		} else {
			$total = 0;
			$highest = 0;
			$lowest = null;
			foreach($this->storage[$name] as $id => $object){
				$total += $object->Difference();
				if($object->Difference() > $highest){
					$highest = $object->Difference();
				}
				
				if($object->Difference() < $lowest || $lowest === null){
					$lowest = $object->Difference();
				}
			}
			
			$this->log->Warning($name, sprintf('%s had %s iterations over %s ms, with an average of %s ms, a high of %s ms and a low of %s ms.', 
				$name, 
				count($this->storage[$name]), 
				$total, 
				($total / count($this->storage[$name])),
				$highest,
				$lowest
			));
		}
		
		unset($this->storage[$name]);
	}
}