<?php
use \Origin\Utilities\Layout;

if(constant('DEBUG') === false){
	class ApocalypsePreventer extends \Origin\Utilities\Types\ErrorLog {
		public function __construct(){
			parent::__construct();
			ini_set('display_errors', 'Off');
			error_reporting(0);
		}
		
		protected function DisplayIssue(){
			if(php_sapi_name() !== 'cli'){
				ob_clean();
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				Layout::Get()->Assign('error', $this->HashValue());
				Layout::Get()->Display('apocalypse.tpl');
			}

			die(sprintf("%s:%s\n\n%s", $this->ErrorString(), $this->LineNumber(), $this->Error()));
		}
	}
	
	$apocalypse = (new ApocalypsePreventer());
	
	set_error_handler([$apocalypse, 'HandleError'], E_ALL ^ E_WARNING ^ E_NOTICE);
	set_exception_handler([$apocalypse, 'HandleException']);
	register_shutdown_function([$apocalypse, 'HandleShutdown']);
}