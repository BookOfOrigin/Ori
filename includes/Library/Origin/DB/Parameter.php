<?php
namespace Origin\DB;

class Parameter {
	private $value;
	private $nullable;
	private $column;
	private $limit_start;
	private $limit_count;
	private $humanreadable;
	private $comparitor = '=';
	private $conjunction = 'AND';
	private $multi_conjunction = 'OR';
	private $subparameters = array();
	
	public function __construct(array $array = array()){
		if(!empty($array)){
			$this->Column((isset($array['Column']) ? $array['Column'] : null));
			$this->Value((isset($array['Value']) ? $array['Value'] : null));
			$this->Comparitor((isset($array['Comparitor']) ? $array['Comparitor'] : null));
			$this->Conjunction((isset($array['Conjunction']) ? $array['Conjunction'] : null));
			$this->HumanReadable((isset($array['HumanReadable']) ? $array['HumanReadable'] : null));
			$this->Subparameters((isset($array['SubParameters']) ? $array['SubParameters'] : null));
			$this->MultiConjunction((isset($array['MultiConjunction']) ? $array['MultiConjunction'] : null));
			$this->LimitStart((isset($array['LimitStart']) ? $array['LimitStart'] : null));
			$this->LimitCount((isset($array['LimitCount']) ? $array['LimitCount'] : null));
			$this->Nullable((isset($array['Nullable']) ? $array['Nullable'] : false));
		}
	}
	
	public function Column($column = null){
		if($column !== null){
			$this->column = $column;
		}
		
		return $this->column;
	}
	
	public function Value($value = null){
		if($value !== null){
			$this->value = $value;
		}
		
		return $this->value;
	}
	
	public function LimitStart($value = null){
		if($value !== null){
			$this->limit_start = $value;
		}
		
		return $this->limit_start;
	}
	
	public function LimitCount($value = null){
		if($value !== null){
			$this->limit_count = $value;
		}
		
		return $this->limit_count;
	}

	public function Comparitor($comparitor = null){
		if($comparitor !== null){
			$this->comparitor = $comparitor;
		}
		
		return $this->comparitor;
	}
	
	public function Conjunction($conjunction = null){
		if($conjunction !== null){
			$this->conjunction = $conjunction;
		}
		
		return $this->conjunction;
	}
	
	public function MultiConjunction($conjunction = null){
		if($conjunction !== null){
			$this->multi_conjunction = $conjunction;
		}
		
		return $this->multi_conjunction;
	}
	
	public function SubParameters(Parameters $subparameters = null){
		if(!empty($subparameters)){
			$this->subparameters = $subparameters;
		}
		
		return $this->subparameters;
	}
	
	public function HumanReadable($value = null){
		if(!empty($value)){
			$this->humanreadable = $value;
		}
		
		return $this->humanreadable;
	}
	
	public function Nullable($value = null){
		if(!empty($value)){
			$this->nullable = $value;
		}
		
		return $this->nullable;
	}
}