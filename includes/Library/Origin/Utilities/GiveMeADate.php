<?php
namespace Origin\Utilities;

use \DateTime;

class GiveMeADate extends \Origin\Utilities\Types\Singleton {
	public function ConvertString($string){
		if(is_numeric($string)){
			return (new DateTime())->setTimestamp($string);
		} else {
			foreach(static::$formats as $format){
				$date = DateTime::createFromFormat($format, $string);
				if($date !== false && (!isset($date->getLastErrors()['errors']) || empty($date->getLastErrors()['errors']))){
					return $date;
				}
			}
			 
			$tmp = strtok($string, " ");
			foreach(static::$formats as $format){
				$date = DateTime::createFromFormat($format, $tmp);
				if($date !== false && (!isset($date->getLastErrors()['errors']) || empty($date->getLastErrors()['errors']))){
					return $date;
				}
			}
			
			// Fall back to stupidity.
			$time = strtotime($string);
			if($time > 0){
				return (new DateTime())->setTimestamp($time);
			}
		}
	}
	
	private static $formats = [
		'Y-m-d H:i:s',
		'Y-m-d 00:00:00',
		'd-m-Y H:i:s',
		'm-d-Y H:i:s',
		'd-m-Y 00:00:00',
		'm-d-Y 00:00:00',
		'm/d/Y 00:00:00',
		'd/m/Y 00:00:00',
		'F Y',
		'Y-m-d\TH:i:s.u+',
		'Y-m-d\TH:i:s.v+',
		'D M d Y H:i:s+',
		'Y-m-d',
		'Ymd',
		'd/m/Y',
		'Y-M-d\TH:i:s\Z'
	];
}