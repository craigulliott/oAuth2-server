<?php

class DBException extends Exception {
	
	private $query;
	
	public function setQuery($query){
		$this->query = $query;
	}
	
	public function getQuery(){
		return $this->query;
	}
	
}
