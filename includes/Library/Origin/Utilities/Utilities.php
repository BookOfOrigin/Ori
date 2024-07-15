<?php
namespace Origin\Utilities;

use \DateTime;
use \Exception;
use \Mailgun\Mailgun;
use \phpseclib\Crypt\RSA;

class Utilities {
    const ALL_CHARACTERS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const ONLY_LETTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const ONLY_NUMBERS = '0123456789';
	/*
	* Generates a random "string" based on length and optional character sets.
	* @return String
	*/
	public static function RandomString($length = 10, $characters = null) {
		if($characters === null){
			$characters = static::ALL_CHARACTERS;
		}
		
		$charactersLength = strlen($characters);
		$randomString = '';

		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}

		return $randomString;
	}
	
	/*
	* Returns a usable array of files if files were uploaded otherwise 
	* @returns array();
	*/
	public static function Uploads(){
		$i = 0;
		$files = array();
		if(!empty($_FILES)){
			foreach ($_FILES as $field => $input) {
				$j = 0;
				$files[$i]['field'] = $field;
				foreach ($input as $property => $value) {
					if (is_array($value)) {
						$j = count($value);
						for ($k = 0; $k < $j; ++$k) {
							$files[$i + $k][$property] = $value[$k];
						}
					} else {
						$j = 1;
						$files[$i][$property] = $value;
					}
				}
				
				$i += $j;
			}
		}
		
		return $files;
	}
	
	/*
	* Takes a message, success and an array of values to create a json encoded application string.
	* Useful for API results.
	* NOTE: Exits!
	*/
	public static function JsonExit($message = null, $success = false, array $values = array(), $boolean = true){
		if($boolean === false){
			$success = ($success === true) ? '1' : '0';
		}

		header('Content-Type: application/json; charset=utf-8');
		exit(print_r(json_encode(array_merge(array('message' => $message, 'success' => $success), $values), JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR), true));
	}
	
	/*
	* Converts bytes in to a human readable string.
	* @return String
	*/
	public static function HumanFileSize($bytes, $decimals = 2) {
   		$size = array(' Bytes',' KiloBytes',' MegaBytes',' GigaBytes',' TeraBytes',' PetaBytes');
    	$factor = floor((strlen($bytes) - 1) / 3);
    	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}

	/*
	* Validates that and MD5 is a valid MD5.
	* @returns bool
	*/
	public static function ValidateMD5($md5 = null){
		return (bool) preg_match('/^[a-f0-9]{32}$/', $md5);
	}

	/*
	* Validates that and SHA1 is a valid SHA1.
	* @returns bool
	*/
	public static function ValidateSHA1($sha1 = null){
		return (bool) preg_match('/^[0-9a-f]{40}$/i', $sha1); 
	}
	
	/*
	* A very simplified fetch URL content with a timeout.
	* Todo: Transition to using Request.
	* @returns string
	*/
	public static function FetchURLContent($url, array $parameters = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

		if($parameters !== null){
			curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);	
		}
		
		$data = curl_exec($curl);
		curl_close($curl);
		
		return $data;
	}

	/*
	* Validates that an email address is at least somewhat sane.
	* @returns bool
	*/
	public static function ValidateEmail($email = null){
		if($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL)){
			return true;
		}
		
		return false;
	}
	
	/*
	* Returns the currently connecting IP regardless of which header it came from.
	* @returns String
	*/
	public static function GetIP(){
		switch(true){
			case (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP']) && $_SERVER['HTTP_CF_CONNECTING_IP'] !== '0.0.0.0'): return $_SERVER['HTTP_CF_CONNECTING_IP'];
			case (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '0.0.0.0'): return $_SERVER['REMOTE_ADDR'];
			case (isset($_SERVER['HTTP_X_REAL_IP']) && !empty($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'] !== '0.0.0.0'): return $_SERVER['HTTP_X_REAL_IP'];
			case (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] !== '0.0.0.0'): return $_SERVER['HTTP_CLIENT_IP'];
			case (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] !== '0.0.0.0'): return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
		}
	}
	
	/*
	* Converts string into Base62.
	* @returns string
	*/
	public static function Base62Encode($data) {
		$outstring = '';
		$l = strlen($data);
		for ($i = 0; $i < $l; $i += 8) {
			$chunk = substr($data, $i, 8);
			$outlen = ceil((strlen($chunk) * 8)/6); //8bit/char in, 6bits/char out, round up
			$x = bin2hex($chunk);  //gmp won't convert from binary, so go via hex
			$w = gmp_strval(gmp_init(ltrim($x, '0'), 16), 62); //gmp doesn't like leading 0s
			$pad = str_pad($w, $outlen, '0', STR_PAD_LEFT);
			$outstring .= $pad;
		}
		return $outstring;
	}
	
	/*
	* Converts base 62 encoded string back into it's raw format.
	* @returns string
	*/
	public static function Base62Decode($data) {
		$outstring = '';
		$l = strlen($data);
		for ($i = 0; $i < $l; $i += 11) {
			$chunk = substr($data, $i, 11);
			$outlen = floor((strlen($chunk) * 6)/8); //6bit/char in, 8bits/char out, round down
			$y = gmp_strval(gmp_init(ltrim($chunk, '0'), 62), 16); //gmp doesn't like leading 0s
			$pad = str_pad($y, $outlen * 2, '0', STR_PAD_LEFT); //double output length as as we're going via hex (4bits/char)
			$outstring .= pack('H*', $pad); //same as hex2bin
		}
		return $outstring;
	}
	
	/*
	* Encrypts a string based on the private key. If no private key is found it generates one then encrypts thestring.
	* @returns string
	*/
	const PUBLIC_KEY = 'hidden/keys/id_rsa.pub';
	const PRIVATE_KEY = 'hidden/keys/id_rsa';
	public static function Encrypt($string){
		if(file_exists(static::PUBLIC_KEY) && file_exists(static::PRIVATE_KEY)){
			$rsa = new RSA();
			$rsa->loadKey(file_get_contents(static::PUBLIC_KEY));
			return static::Base62Encode($rsa->encrypt($string));
		} else {
			if(static::GenerateKeys()){
				return static::Encrypt($string);
			} else {
				throw new Exception('Unable to generate keys for encryption. Please check the path and try again.');
			}
		}
	}

	/*
	* Decrypts a string based on the private key. If no private key is found it generates one then decrypts the string. (Unlikely to work.)
	* @returns string
	*/
	public static function Decrypt($string){
		if(file_exists(static::PUBLIC_KEY) && file_exists(static::PRIVATE_KEY)){
			$rsa = new RSA();
			$rsa->loadKey(file_get_contents(static::PRIVATE_KEY));
			return $rsa->decrypt(static::Base62Decode($string));
		} else {
			if(static::GenerateKeys()){
				return static::Decrypt($string);
			}
		}
	}
	
	/*
	* Converts a DateTime object into a human readable string such as "1 minute ago" or "3 days from now"
	* @returns string
	*/
	public static function FuzzyTime(DateTime $date, $invalid_past = 'a long time ago', $invalid_future = 'a long time from now') {
		$time_formats = array(
			array('max' => 60, 'text' => 'just now'),
			array('max' => 90, 'text' => '1 minute', 'past' => 'ago', 'future' => 'from now'),
			array('max' => 3600, 'text' => 'minutes', 'divider' => 60, 'past' => 'ago', 'future' => 'from now'),
			array('max' => 5400, 'text' => '1 hour', 'past' => 'ago', 'future' => 'from now'),
			array('max' => 86400, 'text' => 'hours', 'divider' => 3600, 'past' => 'ago', 'future' => 'from now'),
			array('max' => 129600, 'text' => '1 day', 'past' => 'ago', 'future' => 'from now'),
			array('max' => 604800, 'text' => 'days', 'divider' => 86400, 'past' => 'ago', 'future' => 'from now'),
			array('max' => 907200, 'text' => '1 week', 'past' => 'ago', 'future' => 'from now'),
			array('max' => 2628000, 'text' => 'weeks', 'divider' => 604800, 'past' => 'ago', 'future' => 'from now'),
			array('max' => 3942000, 'text' => '1 month', 'past' => 'ago', 'future' => 'from now'),
			array('max' => 31536000, 'text' => 'months', 'divider' => 2628000, 'past' => 'ago', 'future' => 'from now'),
			array('max' => 47304000, 'text' => '1 year', 'past' => 'ago', 'future' => 'from now'),
			array('max' => 3153600000, 'text' =>  'years', 'divider' => 31536000, 'past' => 'ago', 'future' => 'from now'),
		);
		
		if($date->getTimestamp() > (new DateTime())->getTimestamp()){
			foreach($time_formats as $row){
				$now = (new DateTime())->modify('+'.$row['max'].' seconds');
				if($now->getTimestamp() > $date->getTimestamp()){
					if(isset($row['divider'])){
						$total = abs(time() - $date->getTimestamp());
						return round(($total / $row['divider'])).' '.$row['text'].(isset($row['future']) ? ' '.$row['future'] : '');
					}
					
					return $row['text'].(isset($row['future']) ? ' '.$row['future'] : '');
				}
			}
			
			return $invalid_future;
		} else {
			foreach($time_formats as $row){
				$now = (new DateTime())->modify('-'.$row['max'].' seconds');
				if($date->getTimestamp() > $now->getTimestamp()){
					if(isset($row['divider'])){
						$total = time() - $date->getTimestamp();
						return round(($total / $row['divider'])).' '.$row['text'].(isset($row['past']) ? ' '.$row['past'] : '');
					}
					
					return $row['text'].(isset($row['past']) ? ' '.$row['past'] : '');
				}
			}
			
			return $invalid_past;
		}
	}
	
	/*
	* Takes a string and converts it into a PNG image.
	* NOTE: Exits!
	*/
	public function StringToImage($string, $font = 4, array $background_color = null, array $text_color = null) {
		header ("Content-type: image/png");
		$string = htmlspecialchars(strip_tags($string));
		$width = ImageFontWidth($font) * strlen($string);
		$height = ImageFontHeight($font);
		$image = @imagecreate($width, $height);
		
		// This execution order matters. Fuck PHP.
		if(is_array($background_color) && count($background_color) === 3){
		    $background_color = array_values($background_color);
		    $bg = imagecolorallocate($image, $background_color[0], $background_color[1], $background_color[2]); //white background
		}
		
		$bg = imagecolorallocate($image, 255, 255, 255); //white background default
		$txt = imagecolorallocate($image, 0, 0, 0); //black text default
		
		if(is_array($text_color) && count($text_color) === 3){
		    $text_color = array_values($text_color);
		    $txt = imagecolorallocate($image, $text_color[0], $text_color[1], $text_color[2]); //black text
		}
		
		imagestring($image, $font, 0, 0,  $string, $txt);
		exit(imagepng($image));
	}
	
	/*
	* Sends an email using mailgun.
	* Should be converted to use multiple email senders.
	* @returns bool
	*/
	public static function SendEmail($email, $subject, $message, array $parameters = array()){
		try {
			$mailgun = new Mailgun(Settings::Get()->Value(['origin', 'mailgun_key']), new \Http\Adapter\Guzzle7\Client());
			
			$to = ltrim(sprintf('%s %s <%s>', 
				isset($parameters['first_name']) ? $parameters['first_name'] : null,
				isset($parameters['last_name']) ? $parameters['last_name'] : null,
				$email
			));
			
			$result = $mailgun->sendMessage(Settings::Get()->Value(['origin', 'mail_domain']), [
				'from' => sprintf('"%s" <%s@%s>', Settings::Get()->Value(['site', 'title']), 'support', Settings::Get()->Value(['site', 'domain'])),
				'to' => $to,
				'h:Reply-To' => sprintf('"%s" <%s@%s>', Settings::Get()->Value(['site', 'title']), 'support', Settings::Get()->Value(['site', 'domain'])),
				'subject' => $subject,
				'html' => $message
			]);
			
			if($result->http_response_code == 200) {
				return true;	
			} else {
				throw new Exception('Mailgun bad response code.');
			}
		} catch (Exception $e) {
			die(print_r($e, true));
			Log::Get()->Warning('Mailgun Error', $e);
			Log::Get()->Warning('Mailgun Response', (isset($result)) ? $result : null);
		}

		return false;
	}

	/*
	* Converts a Javascript offset to a PHP timezone while taking daylight savings time into account :D
	* This took a ton of research, sadly no one will ever look at this lol.
	* @returns string
	*/
	public static function OffsetToTimezone($offset){
		$offset = -((date('I') === '1' ? ($offset + 60) : $offset));
		return timezone_name_from_abbr("", ($offset*60), 0);
	}

	/*
	* Sends the browser a message that we're done processing the data and allows execution to carry on in the back ground.
	* @returns bool
	*/
	public static function ExitContinue(){
		if (is_callable('fastcgi_finish_request')) {
			session_write_close();
			fastcgi_finish_request();
			return true;
		}

		ignore_user_abort(true);
		$serverProtocole = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
		header($serverProtocole.' 200 OK');
		header('Content-Encoding: none');
		header('Content-Length: '.ob_get_length());
		header('Connection: close');

		ob_end_flush();
		ob_flush();
		flush();

		if(session_id()) {
			session_write_close();
		}
		
		return true;
	}
	
	private static function GenerateKeys(){
		$rsa = new RSA();
		$keys = $rsa->createKey();
		return (file_put_contents(static::PUBLIC_KEY, $keys['publickey']) !== false && file_put_contents(static::PRIVATE_KEY, $keys['privatekey']) !== false);
	}
}
