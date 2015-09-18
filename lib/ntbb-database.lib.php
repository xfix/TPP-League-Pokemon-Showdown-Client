<?php
class NTBBDatabase {
	private $database;

	function __construct($string) {
		$this->database = new PDO($string);
	}

	function query($query) {
		return $this->database->query($statement);
	}

	function fetch_assoc($result) {
		return $result->fetch(PDO::FETCH_ASSOC);
	}

	function prepare($query) {
		return $this->database->prepare($query);
	}
}
