<?php
namespace Origin\DB;

use \PDO;
use \PDOException;
use \Origin\Utilities\Settings;
use \Origin\Utilities\Types\Exception;

/*
* Example Call: DB::Get('connection_name_from_config')->Query($query, array('param' => ':param'));
*/
class DB extends \Origin\Utilities\Types\Singleton {
	private $error;
	private $statement;
	private $connection;
	private $connection_parameters;
	
	/*
	* Select back full result set as an array.
	*/
	public function Query($sql, array $parameters = null, $fetch_type = null){
		//\Origin\Log\Log::Get('db')->Warning('sql', $sql);
		//\Origin\Log\Log::Get('db')->Warning('parameters', $parameters);
		if($this->Execute($sql, $parameters)){
			return $this->statement->fetchAll(($fetch_type === null) ? PDO::FETCH_ASSOC : $fetch);
		}
	}

	/*
	* Select just the first row.
	*/
	public function QueryFirstRow($sql, array $parameters = null, $fetch_type = null){
		//\Origin\Log\Log::Get('db')->Warning('sql', $sql);
		//\Origin\Log\Log::Get('db')->Warning('parameters', $parameters);
		if($this->Execute($sql, $parameters)){
			return $this->statement->fetch(($fetch_type === null) ? PDO::FETCH_ASSOC : $fetch_type);
		}
	}

	/*
	* Select the first column.
	*/
	public function QueryFirstColumn($sql, array $parameters = null){
		if($this->Execute($sql, $parameters)){
			return $this->statement->fetchAll(PDO::FETCH_COLUMN);
		}
	}

	/*
	* Select the first column of the first row.
	*/
	public function QueryOne($sql, array $parameters = null){
		if($this->Execute($sql, $parameters)){
			return $this->statement->fetch(PDO::FETCH_COLUMN, 0);
		}
	}
	
	/*
	* Generates an in() statement on the fly inside 
	* a $sql string by replacing ($replace) with the generated SQL.
	* @returns - array of bind values :D
	*/
	public function In(&$sql, $replace, array $parameters, $handle = 'zero', $seperator = ', '){
		$string = null;
		$bind_strings = array();
		$binds = array();
		$total_parameters = 1;
		foreach($parameters as $value){
			$bind = sprintf(':%s1%04d', $handle, $total_parameters);
			$bind_strings[] = $bind;
			$binds[$bind] = $value;
			$total_parameters++;
		}
		
		$sql = str_replace($replace, implode($seperator, $bind_strings), $sql);
		return $binds;
	}

	/*
	* Inserts a record into the database.
	*/
	public function Insert($table, array $parameters = null, $format = null, $ignore = false){
		$binds = array();
		$total_parameters = 1;
		foreach($parameters as $key => $value){
			if($value instanceof \DateTime){
				if($format === null){
					$value = $value->getTimestamp();
				} else {
					$value = $value->format($format);
				}
			}

			if(is_array($value)){
				$value = json_encode($value);
			}
			
			$binds[sprintf(':parameter1%04d', $total_parameters)] = $value;
			$total_parameters++;
		}
		
		$query = sprintf($ignore ? static::$insert_ignore_template : static::$insert_template, $table, implode(', ', array_keys($parameters)), implode(', ', array_keys($binds)));
		return $this->Execute($query, $binds);
	}
	
	public function MassInsert($table, array $parameters = null, $format = null, $ignore = false){
		$keys = null;
		$bind_sql = null;
		$binds = array();
		$total_parameters = 1;
		foreach($parameters as $key => $insert){
			if($key === 0){
				$keys = implode(', ', array_keys($insert));
			}
			
			$tmp_binds = array();
			foreach($insert as $key => $value){
				if($value instanceof \DateTime){
					if($format === null){
						$value = $value->getTimestamp();
					} else {
						$value = $value->format($format);
					}
				}

				if(is_array($value)){
					$value = json_encode($value);
				}

				$tmp_binds[sprintf(':parameter1%06d', $total_parameters)] = $value;
				$binds[sprintf(':parameter1%06d', $total_parameters)] = $value;
				$total_parameters++;
			}
			
			if($bind_sql === null){
				$bind_sql = sprintf('(%s)', implode(', ', array_keys($tmp_binds)));
			} else {
				$bind_sql .= sprintf(', (%s)', implode(', ', array_keys($tmp_binds)));
			}
		}
		//die(print_r(sprintf($ignore ? static::$insert_ignore_multi_template : static::$insert_multi_template, $table, $keys, $bind_sql), true));
		return $this->Execute(sprintf($ignore ? static::$insert_ignore_multi_template : static::$insert_multi_template, $table, $keys, $bind_sql), $binds);
	}
	
	public function LastID(){
		return $this->connection->lastInsertId();
	}

	/*
	* Updates a record in the database.
	*/
	public function Update($table, array $parameters, $where = null, array $where_parameters = null, $format = null){
		$sql = null;
		$binds = array();
		$total_parameters = 1;
		foreach($parameters as $key => $value){
			if($value instanceof \DateTime){
				if($format === null){
					$value = $value->getTimestamp();
				} else {
					$value = $value->format($format);
				}
			}
			
			if(is_array($value)){
				$value = json_encode($value);
			}
			
			$bind_key = sprintf(':parameter1%04d', $total_parameters);
			$sql .= (($sql === null) ? sprintf(static::$set_sql, $key, $bind_key) : ', '.sprintf(static::$set_sql, $key, $bind_key));
			$binds[$bind_key] = $value;
			$total_parameters++;
		}
		
		$query = sprintf(static::$update_template, $table, $sql);
		if($where !== null){
			$query .= sprintf(static::$update_where, $where);
			
			if($where_parameters !== null){
				$binds = array_merge($binds, $where_parameters);
			}
		}
		
		return $this->Execute($query, $binds);
	}
	
	public function Remove($table, $where = null, array $where_parameters = null){
		$sql = null;
		$binds = array();
		$query = sprintf(self::$remove_template, $table, $sql);
		if($where !== null){
			$query .= sprintf(self::$remove_where, $where);
			
			if($where_parameters !== null){
				$binds = array_merge($binds, $where_parameters);
			} else {
				die('No. That\'s not right.');
			}
		} else {
			die('Who let you in here?');
		}
		return $this->Execute($query, $binds);
	}

	/*
	* Lookup database connection information from settings file and setup connection.
	*/
	public function __construct($database_name = null){
		if($database_name === null){
			$database_name = Settings::Get()->Value(['origin', 'default_database']);
			if($database_name === null){
				throw new Exception('Invalid database name passed. Please check the call and try again.');
			}
		}

		$this->connection_parameters = Settings::Get('databases')->Values([$database_name]);
		if(!$this->Connect()){
			throw new Exception('Unable to connect to database: '.$database_name.' - '.$this->error);
		}
	}

	/*
	* Connect to the database server.
	*/
	private function Connect(){
		$dsn = sprintf('%s:host=%s;dbname=%s;port=%s',
			$this->connection_parameters->offsetGet('type'),
			$this->connection_parameters->offsetGet('hostname'),
			$this->connection_parameters->offsetGet('database'),
			$this->connection_parameters->offsetGet('port')
		);
		
		$options = array(
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		
		try {
			$this->connection = new PDO($dsn, $this->connection_parameters->offsetGet('username'), $this->connection_parameters->offsetGet('password'), $options);
			return true;
		} catch(PDOException $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}

	/*
	* Execute some SQL.
	*/
	public function Execute($sql, array $parameters = null, $attempts = 0){
		try {
			$this->statement = $this->connection->prepare($sql);
			if($parameters !== null){
				foreach($parameters as $key => $value){
					if($value instanceof \DateTime){
						$value = $value->getTimestamp();
					}

					$this->statement->bindValue($key, $value, $this->GetType($value));
				}
			}

			return $this->statement->execute();
		} catch(PDOException $e){
			if(strpos($e->getMessage(), 'server has gone away') !== false && $attempts < 4){
				if($this->Connect()){
					return $this->Execute($sql, $parameters, $attempts++);
				}
			} else {
				throw $e;
			}
		}
	}
	
    public function Hostname(){
        if($this->connection_parameters !== null){
            return $this->connection_parameters->offsetGet('hostname');
        }
    }


	/*
	* Get type of value.
	*/
	private function GetType($value){
		switch (true) {
			case is_int($value):
				$type = PDO::PARAM_INT;
				break;
			case is_bool($value):
				$type = PDO::PARAM_BOOL;
				break;
			case is_null($value):
				$type = PDO::PARAM_NULL;
				break;
			default:
				$type = PDO::PARAM_STR;
				break;
		}

		return $type;
	}

	/*
	* SQL builder dump.
	*/
	private static $update_template = <<<'SQL'
UPDATE
	%s
SET
	%s
SQL;
	
	private static $insert_template = <<<'SQL'
INSERT INTO %s (
	%s
) VALUES (
	%s
)
SQL;
	
		private static $insert_ignore_template = <<<'SQL'
INSERT IGNORE INTO %s (
	%s
) VALUES (
	%s
)
SQL;
	
	private static $insert_multi_template = <<<'SQL'
INSERT INTO %s (
	%s
) VALUES %s;
SQL;
	
		private static $insert_ignore_multi_template = <<<'SQL'
INSERT IGNORE INTO %s (
	%s
) VALUES %s;
SQL;

	private static $update_where = <<<'SQL'
 WHERE
	%s
SQL;

	private static $set_sql = <<<'SQL'
 %s = %s
SQL;
	
	private static $remove_template = <<<'SQL'
DELETE FROM 
	%s
SQL;
	
	private static $remove_where = <<<'SQL'
 WHERE
	%s
SQL;
}