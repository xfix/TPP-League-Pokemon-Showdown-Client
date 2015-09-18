<?php
class NTBBDatabase {
	private $database;
	public $prefix = 'ntbb_';

	function __construct($string) {
		$this->database = new PDO($string);
	}

	function query($query) {
		return $this->database->query($query);
	}

	function fetch_assoc($result) {
		return $result->fetch(PDO::FETCH_ASSOC);
	}

	function prepare($query) {
		return $this->database->prepare($query);
	}

	function escape($value) {
		return substr($this->database->quote($value), 1, -1);
	}
}
