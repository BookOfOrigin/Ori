<?php
namespace Origin\Utilities\Fetch;

use \Exception;
use \Origin\Utilities\Bucket\Bucket;
use \Origin\Utilities\Bucket\Common;
use \Origin\Utilities\Types\CustomStorage;

class MultiRequest {
	use Bucket, Common {
		Any as Requests;
	}
	
	public function __construct(){
		$this->Requests((new CustomStorage()));
	}
	
	public function AddRequest(Request $request){
		$this->Requests()->append($request);
	}
	
	/*
	* When you don't have fancy requests you can just supply a list of urls.
	*/
	public function FetchResults(array $urls){
		foreach($urls as $url){
			$tmp = new Request();
			$tmp->URL($url);
			
			$this->Requests()->append($tmp);
		}
		
		return $this->Execute();
	}
	
	public function Execute(){
		$handler = array();
		
		$processor = curl_multi_init();
		foreach($this->Requests() as $key => $request){
			if($request->Result() !== null){
				throw new Exception('You can\'t make two requests from one request object. Make a new object.');
			}
			
			$request->Result((new Result()));
			curl_multi_add_handle($processor, $request->FetchCURL());
		}
		
		do {
			curl_multi_exec($processor, $running);
		} while($running > 0);
		
		foreach($this->Requests() as $key => $request){
			$request->Result()->Body(curl_multi_getcontent($request->FetchCURL()));
			$request->Result()->ResponseCode((int) curl_getinfo($request->FetchCURL(), CURLINFO_HTTP_CODE));
			$request->Result()->Completed(true);
			curl_multi_remove_handle($processor, $request->FetchCURL());
		}
		
		return true;
	}
}