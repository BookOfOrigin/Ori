<?php
namespace Origin\Utilities\Fetch;

use \Origin\Utilities\Bucket\Bucket;
use \Origin\Utilities\Bucket\Common;

class Proxy extends \Origin\DB\DatabaseAssistant {
	public $table = 'fetch_proxies';
	use Bucket, Common {
		Number as ID;
		Blob as Connection;
		Blob as Username;
		Blob as Password;
		Hash as Headers;
		Boolean as Down;
		Boolean as Deleted;
		Date as Genesis;
		Date as Mutation;
	}
}