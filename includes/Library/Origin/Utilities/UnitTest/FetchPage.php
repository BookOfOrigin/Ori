<?php
namespace Origin\Utilities\UnitTest;

use \Origin\Utilities\Bucket\Bucket;
use \Origin\Utilities\Bucket\Common;

class FetchPage {
	use Bucket, Common {
		Blob as URL;
		Hash as GetParameters;
		Hash as PostParameters;
		Blob as UserAgent;
		Hash as Headers;
		Any as HeaderCallback;
	}
	
	const GENERIC_USERAGENT = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
	public function __construct(){
		$this->Headers(['accept-language' => 'en-US,en;q=0.9']);
		$this->UserAgent(static::GENERIC_USERAGENT);
	}
	
	public function ResetCookies(){
		file_put_contents(TestRunner::COOKIE_FILE, '');
	}
	
	public function Fetch(){
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, function($c, $line){
			if($this->HeaderCallback() !== null){
				$tmp = $this->HeaderCallback();
				$tmp($line);
			}
			
			return strlen($line);
		});
		
		curl_setopt($curl, CURLOPT_URL, $this->URL().$this->DetermineGETParameters());
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_USERAGENT, $this->UserAgent());
		curl_setopt($curl, CURLOPT_COOKIEFILE, TestRunner::COOKIE_FILE);
		
		if($this->Headers() === null){
			curl_setopt($curl, CURLOPT_HEADER, false);
		} else {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->ProcessedHeaders());
		}
		
		if($this->PostParameters() !== null){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->PostParameters()));
		}
		
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		
		$result = curl_exec($curl);
		curl_close($curl);
		
		return $result;
	}
	
	private function DetermineGETParameters(){
		if($this->GetParameters() !== null){
			return '?'.http_build_query($this->GetParameters());
		}
	}
	
	private function ProcessedHeaders(){
		$result = array();
		foreach($this->Headers() as $key => $header){
			$result[] = $key.': '.$header;
		}
		
		return $result;
	}
}