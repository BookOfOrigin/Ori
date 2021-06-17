<?php
namespace Origin\Log;

use \Origin\Utilities\Settings;

/* Requires a slack_url setting in 'site' */
class Slack extends \Origin\Utilities\Types\Singleton {
	public function Notify($message){
		$slack = curl_init(Settings::Get()->Value(['site', 'slack_url']));
		curl_setopt($slack, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($slack, CURLOPT_POSTFIELDS, json_encode(array('text' => $message)));
		curl_setopt($slack, CURLOPT_RETURNTRANSFER, false);
		//curl_setopt($slack, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_string)));
		curl_exec($slack);
		
		return true;
	}
}