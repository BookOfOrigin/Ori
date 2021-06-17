<?php
namespace Origin\Utilities\Types;

use \Origin\DB\DB;
use \SplFixedArray;
use \Origin\Utilities\Settings;
use \Origin\Utilities\Utilities;
use \Origin\Utilities\Bucket\Bucket;
use \Origin\Utilities\Bucket\Common;

class ErrorLog extends \Origin\DB\DatabaseAssistant {
	public $table = 'site_error_log';
	use Bucket, Common {
		Number as ID;
		Blob as LineNumber;
		Blob as ErrorString;
		Blob as File;
		Blob as Line;
		Blob as Error;
		Blob as HashValue;
		Blob as Count;
		Blob as Server;
		Boolean as Deleted;
		Date as Genesis;
		Date as Mutation;
	}
	
	private $memory;
	public function __construct(){
		$this->memory = new SplFixedArray(65536);

		try {
			parent::__construct();
		} catch(\Exception $e){}
	}
	
	public function HandleError($level, $error, $file = null, $line = null, $context = null){
		$this->memory = null;
		$this->LineNumber((string) $line);
		$this->Server(Settings::Get('hostname')->Value(['hostname']));
		$this->ErrorString((string) $error);
		$this->File((string) $file);
		$this->Error((string) $error);

		$this->Finalize();
	}

	public function HandleException(\Throwable $exception){
		$this->memory = null;
		$this->LineNumber((string) $exception->getLine());
		$this->Server(Settings::Get('hostname')->Value(['hostname']));
		$this->ErrorString((string) $exception->getMessage());
		$this->File((string) $exception->getFile());
		$this->Error((string) $exception->getTraceAsString());

		$this->Finalize();
	}

	public function HandleShutdown(){
		$this->memory = null;
		$error = error_get_last();
		if(isset($error['type'])){
			if($error['type'] === E_ERROR || $error === E_PARSE){
				$this->Server(Settings::Get('hostname')->Value(['hostname']));
				$this->ErrorString((string) ($error['message'] ?? null));
				$this->LineNumber((string) ($error['line'] ?? null));
				$this->File((string) ($error['file'] ?? null));
				$this->Error((string) print_r(debug_backtrace(), true));
				$this->Finalize();
			}
		}
	}
	
	public function Upsert(){
		try {
			if($this->database->QueryOne(static::$error_exists, ['hash' => $this->HashValue()]) > 0){
				$this->database->Execute(static::$increment_error, ['hash' => $this->HashValue()]);
			} else {
				$this->Insert();
			}
		} catch(\Exception $e){
			\Origin\Log\Telegram::Get()->Notify($e->getMessage(), 'high');
		}
	}

	public function PopulateHash(){
		if($this->LineNumber() !== null && $this->File() !== null){
			$hash = ['line' => $this->LineNumber(), 'file' => $this->File()];
		} else {
			$hash = ['error' => $this->Error()];
		}

		if($this->ErrorString() !== null){
			$hash['errorstring'] = $this->ErrorString();
		}

		$this->HashValue(hash('sha256', json_encode($hash)));
	}

	protected function Finalize(){
		try {
			$this->PopulateHash();
			$this->Log();
			$this->Upsert();
		} catch(\Exception $e){
			\Origin\Log\Telegram::Get()->Notify($e->getMessage(), 'high');
		}
		
		$this->DisplayIssue();
	}

	const LOG_FILE = 'hidden/logs/log.log';
	protected function Log(){
		if(file_exists(static::LOG_FILE) && is_writable(static::LOG_FILE)){
			$error_string = print_r($this->things, true);
			if(stripos($error_string, 'Unable to connect to database') === false){
				file_put_contents(
					static::LOG_FILE, 
					'A error has occured in production mode:'. $error_string, 
					FILE_APPEND | LOCK_EX
				);
			}
		}
	}

	protected function DisplayIssue(){
		die(print_r('A 500 error has occured. Please contact the site owner if this issue persists.'));
	}

	protected static $increment_error = <<<'SQL'
UPDATE
	site_error_log
SET
	Count = Count + 1
WHERE
	HashValue = :hash
SQL;
	
	protected static $error_exists = <<<'SQL'
SELECT
	count(ID)
FROM
	site_error_log
WHERE
	HashValue = :hash
SQL;
}