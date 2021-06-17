<?php
namespace Origin\Utilities\UnitTest;

use \Origin\Utilities\Settings;
use \Origin\Utilities\Utilities;

class TestRunner extends \Origin\Utilities\Types\Singleton {
	const COOKIE_FILE = 'hidden/cache/unit_test_cookies.txt';
	const TEST_DIR = 'includes/Library/Origin/Utilities/UnitTest/Tests/';
	const TEST_NAMESPACE = '\\Origin\\Utilities\\UnitTest\\Tests\\%s';
	public function RunTests(){
		$passed = array();
		$failed = array();
		
		foreach(array_diff(scandir(static::TEST_DIR), ['..', '.']) as $name){
			$class = sprintf(static::TEST_NAMESPACE, (strtok($name, '.')));
			file_put_contents(static::COOKIE_FILE, ''); // Reset cookie file.
			
			$test = new $class();
			if($test->Setup()){
				if($test->Execute()){
					$passed[] = $test;
				} else {
					$failed[] = $test;
				}
			} else {
				$failed[] = $test;
			}
		}
		
		if(count($failed) === 0){
			echo "\nAll Tests Passed!\n";
		} else {
			echo sprintf("%s of %s Tests Passed\n\n", count($passed), (count($passed) + count($failed)));
			foreach($failed as $test){
				echo sprintf("%s failed on %s.\n", $test->Name(), $test->LastFailure());
				
				foreach(Settings::Get()->Values(['origin', 'failure_emails']) as $email){
					//Utilities::SendEmail($email, 'Unit Test Failure', sprintf('%s: %s', $test->Name(), $test->LastFailure()));
				}
			}
		}
	}
	
	public function ListTests(){
		$result = array();
		foreach(array_diff(scandir(static::TEST_DIR), ['..', '.']) as $name){
			$class = sprintf(static::TEST_NAMESPACE, (strtok($name, '.')));
			$test = new $class();
			$result[$test->Name()] = $test->Description();
		}
		
		return $result;
	}
	
	public function RunTest($test){
		if(empty($test)){
			die(print_r("You must specify which test you wish to execute, EG: --unittest=TestName.\n"));
		}
		
		$passed = array();
		$failed = array();
		
		foreach(array_diff(scandir(static::TEST_DIR), ['..', '.']) as $name){
			if(stripos($name, $test) !== false){
				$class = sprintf(static::TEST_NAMESPACE, (strtok($name, '.')));
				file_put_contents(static::COOKIE_FILE, ''); // Reset cookie file.

				$st = new $class();
				if($st->Setup()){
					if($st->Execute()){
						$passed[] = $st;
					} else {
						$failed[] = $st;
					}
				} else {
					$failed[] = $st;
				}
			}
		}
		
		if(count($failed) === 0){
			echo "\nAll Tests Passed!\n";
		} else {
			echo sprintf("%s of %s Tests Passed\n\n", count($passed), (count($passed) + count($failed)));
			foreach($failed as $st){
				echo sprintf("%s failed on %s.\n", $st->Name(), $st->LastFailure());
				
				foreach(Settings::Get()->Values(['origin', 'failure_emails']) as $email){
					//Utilities::SendEmail($email, 'Unit Test Failure', sprintf('%s: %s', $st->Name(), $st->LastFailure()));
				}
			}
		}
	}
}