<?php

namespace BlackFox2;

class ExceptionSQL extends Exception {

	public $SQL;

	public function __construct($message, $SQL) {
		parent::__construct($message);
		$this->SQL = $SQL;
	}

}