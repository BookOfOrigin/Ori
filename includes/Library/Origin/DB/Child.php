<?php
namespace Origin\DB;

use \Origin\DB\DatabaseAssistant;

class Child {
	private $parent_column;
	private $child_column;
	private $parent_storage;
	private $parameters;
	private $child;
	
	public function __construct($parent_column, $child_column, $parent_storage, DatabaseAssistant $child, Parameters $parameters = null){
		$this->parent_column = $parent_column;
		$this->child_column = $child_column;
		$this->parent_storage = $parent_storage;
		$this->parameters = $parameters;
		$this->child = $child;
	}
	
	public function ParentColumn(){
		return $this->parent_column;
	}
	
	public function ChildColumn(){
		return $this->child_column;
	}
	
	public function ParentStorage(){
		return $this->parent_storage;
	}
	
	public function Child(){
		return $this->child;
	}
	
	public function Parameters(){
		return $this->parameters;
	}
}