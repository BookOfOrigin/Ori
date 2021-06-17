<?php
namespace Origin\Utilities;

use \Origin\Utilities\Types\Hash;
use \Origin\Utilities\Types\Exception;

/*
* Settings are awesome, storing them and retrieving them aren't usually so awesome.
* Settings can be retrieved from json files via a simple call now: Settings::Get('settings')->Value(['site', 'title']);
* For more information see the documentation on Ori which I will likely have written before anyone reads this comment.
*/
class Settings extends \Origin\Utilities\Types\Singleton {
	public $values;
	// Even Settings need settings :)
	const CONFIG_LOCAL = 'local/';
	const CONFIG_CACHE = 'cache/';
	const CONFIG_BASE_PATH = 'hidden/config/';
	const CONFIG_FILE_EXTENSION = '.json';
	
	/*
	* Retrieves a single string, integer or float from the requested settings file.
	* Should the setting requested be an array (Hash) this will throw an error.
	*/
	public function Value(array $child = null){
		$values = $this->FindValues($child);
		if($values instanceof Hash){
			throw new Exception('Expected setting to be a string or number, setting is an array.');
		}
		
		if(\ctype_digit($values)){
			return (int) $values;
		}
		
		return $values;
	}
	
	/*
	* Retrieves an array (Hash) from the requested settings file.
	* Should the setting requested not be an array (Hash) this will throw an error.
	*/
	public function Values(array $child = null){
		$values = $this->FindValues($child);
		if(!($values instanceof Hash)){
			throw new Exception('Expected settings to be an array, got: '.gettype($values));
		}
		
		return $values;
	}
	
	/*
	* Configuration and private functions.
	*/
	private $version;
	private $cache;
	private $local;
	private $original;
	/*
	* Retrieves and converts a json array of settings into a Hash object.
	* Said object is stored for the duration of the execution for any subsequent calls to this class for efficiency reasons.
	*/
	public function __construct($version = null){
		$this->version = ($version !== null) ? $version : 'settings';
		
		if($this->Preload() === false){
			throw new Exception('Unable to locate settings file with the name: '.$this->version.static::CONFIG_FILE_EXTENSION);
		}
		
		$this->values = include($this->cache);
	}
	
	private function Preload(){
		$this->local = static::CONFIG_BASE_PATH.static::CONFIG_LOCAL.$this->version.static::CONFIG_FILE_EXTENSION;
		$this->cache = static::CONFIG_BASE_PATH.static::CONFIG_CACHE.$this->version.static::CONFIG_FILE_EXTENSION;
		$this->original = static::CONFIG_BASE_PATH.$this->version.static::CONFIG_FILE_EXTENSION;
		
		// Check this as fast as possible ;) 
		if(file_exists($this->cache) && filemtime($this->original) <= filemtime($this->cache) && (!file_exists($this->local) || filemtime($this->local) <= filemtime($this->cache))){
			return true;
		}
		
		if($this->UpdateCache()){
			return true;
		}
		
		return false;
	}
	
	private function UpdateCache(){
		$local = array();
		if(file_exists($this->local) !== false){
			$local = json_decode(file_get_contents($this->local), true);
			if(!is_array($local)){
				throw new Exception('Invalid local settings file: '.$this->version.static::CONFIG_FILE_EXTENSION);
			}
		}

		$original = json_decode(file_get_contents($this->original), true);
		if(!is_array($original)){
			throw new Exception('Invalid settings file: '.$this->version.static::CONFIG_FILE_EXTENSION);
		}

		if(file_put_contents($this->cache, $this->GenerateCache(array_replace_recursive($original, $local))) !== false){
			return true;
		}
		
		return false;
	}
	
	/*
	* Repeated code between Value() and Values();
	* Gets the desired child/children from the cached json Hash and returns it.
	*/
	private function FindValues(array $child = null, $values = null){
		if($child === null){
			throw new Exception('No setting value was passed, please pass a valid setting name.');
		}
		
		$values = $this->values;
		foreach($child as $key => $name){
			if(!isset($values[$name])){
				throw new Exception('Invalid setting name passed. Please check the call and try again. '.$key.' => '.$name);
			}
			
			$values = $values[$name];
		}
		
		if(is_array($values)){
			$result = new Hash();
			$result->Load($values);
			return $result;
		}
		
		return $values;
	}
	
	private function PrettyPrint($array){
		return str_replace('    ', "\t", json_encode($array, JSON_PRETTY_PRINT));
	}
	
	private function GenerateCache(array $parts, $first = true){
		$result = [];
		foreach($parts as $key => $value){
			if(is_array($value)){
				$result[] = sprintf(static::$array_format_value, str_replace('"', '\"', $key), $this->GenerateCache($value, false));
			} else {
				$result[] = sprintf(static::$array_format, str_replace('"', '\"', $key), str_replace('"', '\"', $value));
			}
		}
		
		return $first === true ? sprintf(static::$finalize_format, sprintf(static::$object_format, implode(', ', $result))) : sprintf(static::$object_format, implode(', ', $result));
	}
	
	private static $finalize_format = <<<'OBJ'
<?php
return %s;
OBJ;
	
	private static $array_format = <<<'OBJ'
"%s" => "%s"
OBJ;
	
	private static $array_format_value = <<<'OBJ'
"%s" => %s
OBJ;
	
	private static $object_format = <<<OBJ
array(%s)
OBJ;
}