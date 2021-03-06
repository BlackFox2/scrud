<?php

namespace BlackFox2;

class Schema {

	use Instance;

	/** @var Database коннектор базы данных */
	protected $DB;

	/** @var SCRUD[] */
	protected $Tables;

	public function __construct(Database $DB) {
		$this->DB = $DB;
	}

	public function SetTables(array $Tables) {
		$this->Tables = $Tables;
	}

	public function Synchronize() {
		foreach ($this->Tables as $Table) {
			$Table->DropConstraints();
		}
		foreach ($this->Tables as $Table) {
			$Table->Synchronize();
		}
		foreach ($this->Tables as $Table) {
			$Table->CreateConstraints();
		}
	}

	public function Drop() {
		foreach (array_reverse($this->Tables) as $Table) {
			$Table->Drop();
		}
	}
}