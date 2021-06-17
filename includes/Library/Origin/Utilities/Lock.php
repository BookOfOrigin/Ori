<?php
namespace Origin\Utilities;

use \DateTime;

class Lock extends \Origin\Utilities\Types\Singleton {
	const LOCK_FOLDER = 'hidden/locks/';
	public function Prune(){
		if ($handle = opendir($this->GetLockFileDirectory())) {
			$count = 0;
			$total_pruned = 0;
			while (($file = readdir($handle)) !== false) {
				if(strpos($file, '.lock') !== false){
					$prune = false;
					$data = json_decode(file_get_contents($this->GetLockFileDirectory().$file), true);
					
					if(empty($data) || $data === false){
						$prune = true;
					} else {
						if(isset($data['expiration_date'])){
							if(isset($data['expiration_date']['date'])){
								$date = DateTime::createFromFormat('Y-m-d H:i:s.u', $data['expiration_date']['date']);
								if($date->getTimestamp() < (new DateTime())->getTimestamp()){
									$prune = true;
								}
							} elseif((new DateTime())->getTimestamp() > $data['expiration_date']){
								$prune = true;
							}
						} elseif(isset($data['creation_date'])){
							if((new DateTime())->modify('-1 day')->getTimestamp() > $data['creation_date']){
								$prune = true;
							}
						} else {
							$prune = true;
						}
					}
					
					if($prune === true){
						$total_pruned++;
						echo sprintf("Pruned lock file: %s\n", $file);
						unlink($this->GetLockFileDirectory().$file);
					}
				}
				
				usleep(5000);
				$count++;
			}
			
			closedir($handle);
			echo sprintf("Pruned %s files.\n", $total_pruned);
		} else {
			echo "Couldn't open lock folder.\n";
		}
	}
	
	public function RequestLock($name, array $parameters = array()){
		if(!$this->LockExists($name)){
			$parameters['creation_date'] = (new DateTime())->getTimestamp();
			file_put_contents($this->GetLockFileName($name), json_encode($parameters));
			return true;
		}
		
		return false;
	}
	
	public function ReleaseLock($name){
		return !file_exists($this->GetLockFileName($name)) || unlink($this->GetLockFileName($name));
	}
	
	public function LockExists($name){
		return file_exists($this->GetLockFileName($name));
	}
	
	public function LockAge($name){
		if($this->LockExists($name)){
			return (new DateTime())->setTimestamp(filemtime($this->GetLockFileName($name)));
		}
	}
	
	public function GetContent($name){
		if($this->LockExists($name)){
			return json_decode(file_get_contents($this->GetLockFileName($name)), true);
		}
	}
	
	private function GetLockFileName($name){
		return $this->GetLockFileDirectory().$name.'.lock';
	}

	private function GetLockFileDirectory(){
		return getcwd().DIRECTORY_SEPARATOR.static::LOCK_FOLDER;
	}
}
