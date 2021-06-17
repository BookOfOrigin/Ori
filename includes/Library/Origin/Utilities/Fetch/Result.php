<?php
namespace Origin\Utilities\Fetch;

use \DOMDocument;
use \Origin\Utilities\Bucket\Bucket;
use \Origin\Utilities\Bucket\Common;
use \Origin\Utilities\Types\CustomStorage;

class Result {
	use Bucket, Common {
		Blob as Body;
		Any as Headers;
		Any as RawHeaders;
		Any as FullHeaders;
		Number as ResponseCode;
		Boolean as Completed;
	}
	
	public function __construct(){
		$this->Headers((new CustomStorage()));
		$this->RawHeaders((new CustomStorage()));
		$this->FullHeaders((new CustomStorage()));
		$this->Completed(false);
	}
	
	private $json;
	public function JSON(){
		if($this->json === null){
			$this->json = json_decode($this->Body(), true);
		}
		
		return $this->json;
	}
	
	private $document;
	public function Document(){
		if($this->document === null){
			$document = new DOMDocument();
			libxml_use_internal_errors(true);
			if($document->loadHTML($this->Body())){
				$this->document = $document;
			}
		}
		
		return $this->document;
	}
}
