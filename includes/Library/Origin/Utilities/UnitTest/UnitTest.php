<?php
namespace Origin\Utilities\UnitTest;

use \Exception;
use \Origin\Utilities\Settings;

abstract class UnitTest {
	protected $name;
	public function Name(){
		return $this->name;
	}
	
	protected $description;
	public function Description(){
		return $this->description;
	}
	
	abstract public function Setup();
	
	private $actions = array();
	public function AddPosit($name, callable $function){
		$this->actions[$name] = $function;
	}
	
	public function Execute(){
		$variables = array();
		foreach($this->actions as $name => $function){
			$this->current_execution = $name;
			try {
				if(!$function($variables)){
					$this->last_failure = $name;
					return false;
				}
			} catch(Exception $e){
				$this->last_failure = $name;
				$this->last_exception = $e;
				return false;
			}
		}
		
		echo sprintf("%s PASSED\n", $this->name);
		return true;
	}
	
	private $last_exception;
	public function LastException(){
		return $this->last_exception;
	}
	
	private $last_failure;
	private $current_execution;
	public function LastFailure(){
		return $this->last_failure;
	}
	
	public function HandleErrors(){
		set_error_handler([$this, "ApocalypsePreventer"], E_ALL ^ E_WARNING ^ E_NOTICE);
		set_exception_handler([$this, "ApocalypsePreventer"]);
		register_shutdown_function([$this, "DetectApocalypse"]);
	}
	
	public function ApocalypsePreventer($number = null, $string = null, $file = null, $line = null, $error = null){
		echo print_r(func_get_args());
		foreach(Settings::Get()->Values(['origin', 'failure_emails']) as $email){
			Utilities::SendEmail($email, 'Unit Test Failure', sprintf('%s: %s<br><br>%s<br><br>%s', $this->name, $this->current_execution, print_r($this->last_exception, true), print_r($error, true)));
		}
	}
	
	public function DetectApocalypse(){
		$error = error_get_last();
		if(isset($error['type'])){
			if($error['type'] === E_ERROR || $error === E_PARSE){
				$this->ApocalypsePreventer(null, null, null, null, $error);
			}
		}
	}
	
	protected function FQDN(){
		return Settings::Get()->Value(['site', 'fqdn']);
	}
}