<?php

namespace BlackFox2;

abstract class Database {

	public $database;

	abstract public function __construct($params);

	/**
	 * Execute SQL query and return a result as array
	 *
	 * @param string $SQL
	 * @param string $key
	 * @return mixed
	 * @throws ExceptionSQL
	 */
	abstract public function Query($SQL, $key = null);

	/**
	 * Execute SQL query and return a result as value of ID, which has been inserted
	 *
	 * @param string $SQL
	 * @param string $increment
	 * @return int|string
	 * @throws ExceptionSQL
	 */
	abstract public function QuerySingleInsert($SQL, $increment = null);

	/**
	 * Process data escape before insert it into SQL query
	 *
	 * @param string $data
	 * @return string
	 */
	abstract public function Escape($data);

	abstract public function Quote($data);

	abstract public function Random();

	public function StartTransaction() {
		$this->Query('START TRANSACTION');
	}

	public function Rollback() {
		$this->Query('ROLLBACK');
	}

	public function Commit() {
		$this->Query('COMMIT');
	}

	abstract public function DropTableConstraints($table);

	/**
	 * @param string $table code of the table
	 * @param array $structure
	 * @throws Exception
	 */
	abstract public function SynchronizeTable($table, $structure);

	/**
	 * @param $table
	 * @param $structure
	 * @throws Exception
	 */
	abstract public function CreateTableConstraints($table, $structure);

	abstract public function CompileSQLSelect(array $parts);

	public function Truncate($table) {
		$this->Query("TRUNCATE TABLE {$table}");
	}

	public function Drop($table) {
		$this->Query("DROP TABLE IF EXISTS {$table}");
	}
}