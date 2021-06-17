<?php
namespace Origin\Utilities;

use \SlashTrace\SlashTrace;
use \SlashTrace\EventHandler\DebugHandler;

/*
* This magic class allows you to call upon the debugger if it's available and if not it'll do nothing!
*/
class Debugger extends \Origin\Utilities\Types\Singleton {
	private $trace;
	public function __construct(){
		if($this->Sandbox()){
			$this->Init();
		}
	}
	
	public function Sandbox(){
		return constant('DEBUG');
	}
	
	public function Init(){
		ini_set('display_errors', 'On');
		error_reporting(E_ALL);

		$this->trace = new SlashTrace();
		$this->trace->addHandler(new DebugHandler());
		$this->trace->register();
	}
	
	public function __call($name, $arguments){
		if($this->trace !== null){
			if(method_exists($this->trace, $name)){
				call_user_func_array([$this->trace, $name], $arguments);
			}
		}
	}
}