<?php
namespace Origin\Utilities\Profile;

use \Origin\Utilities\Bucket\Bucket;
use \Origin\Utilities\Bucket\Common;

class GenericTiming implements \Origin\Utilities\Profile\Interfaces\iTimingHandler {
	use Bucket, Common {
		Blob as Name;
		Blob as File;
		Number as Line;
	}
	
	private $current = null;
	private $stack = [];
	
	public function Start(){
		if($this->current !== null){
			if($this->current->Stop() === null){
				$this->current->Stop(microtime(true));
			}
			
			$this->current = null;
		}
		
		$this->current = new GenericPair();
		$this->stack[] = $this->current;
	}
	
	public function Stop(){
		if($this->current !== null){
			if($this->current->Stop() === null){
				$this->current->Stop(microtime(true));
			}
			
			$this->current = null;
		}
	}
	
	public function FirstStart(){
		if(count($this->stack) > 0){
			return $this->stack[0]->Start();
		}
	}
	
	public function LastStop(){
		if(count($this->stack) > 0){
			$last = $this->stack[count($this->stack ) - 1];
			if($last->Stop() === null){
				$last->Stop(microtime(true));
			}
			
			return $last->Stop();
		}
	}
	
	public function Stack(){
		return $this->stack;
	}
	
	public function jsonSerialize(){
		$total = 0;
		$highest = 0;
		$lowest = null;
		foreach($this->stack as $pair){
			$time = $pair->Difference();
			$total += $time;
			if($time < $lowest || $lowest === null){
				$lowest = $time;
			}
			
			if($time > $highest){
				$highest = $time;
			}
		}
		
		return array_merge($this->things, ['Total' => $total, 'Highest' => $highest, 'Lowest' => $lowest, 'Count' => count($this->stack)]);
	}
	
	private $difference;
	public function Difference(){
		if($this->difference === null){
			$total = 0;
			foreach($this->stack as $pair){
				$total += $pair->Difference();
			}
			
			$this->difference = $total;
		}
		
		return $this->difference;
	}
	
	const TIME_FORMAT = 'Total time for %s (%s:%s): %s ms';
	public function Format(){
		if(count($this->stack) === 1){
			return sprintf(static::TIME_FORMAT, $this->Name(), $this->File(), $this->Line(), $this->stack[0]->Difference());
		}
		
		$total = 0;
		$highest = 0;
		$lowest = null;
		foreach($this->stack as $pair){
			$time = $pair->Difference();
			$total += $time;
			if($time < $lowest || $lowest === null){
				$lowest = $time;
			}
			
			if($time > $highest){
				$highest = $time;
			}
		}

		$size = count($this->stack);
		return sprintf('%s (%s:%s) had %s iterations over %s ms, with an average of %s ms, a high of %s ms and a low of %s ms.', $this->Name(), $this->File(), $this->Line(), $size, $total, round(($total / $size), 2), $highest, $lowest);
	}
}