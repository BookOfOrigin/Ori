<?php
namespace Origin\Utilities\Profile\Interfaces;

use \Origin\Utilities\Types\CustomStorage;

interface iStorage {
	public function Shutdown(CustomStorage $timings);
}