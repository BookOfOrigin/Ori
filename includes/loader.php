<?php
// Required global constants.
define('DEBUG', file_exists('hidden/config/sandbox.json'));
define('DEVELOPMENT', !file_exists('hidden/config/production.json'));

// Composer Integration Support
if(file_exists('vendor/autoload.php')){
  require_once('vendor/autoload.php');
}

// Autoloader setup and configuration.
require_once('includes/Library/Origin/Autoload/Autoload.php');
spl_autoload_register('\Origin\Autoload\Autoload::Load');
\Origin\Utilities\Debugger::Get();