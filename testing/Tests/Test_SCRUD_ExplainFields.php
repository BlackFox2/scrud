<?php

namespace BlackFox2;

class Test_SCRUD_ExplainFields extends Test {
	public $name = 'SCRUD: ExplainFields';

	public $limit = 100;

	/** @var SimpleTable */
	private $SimpleTable;

	/** Getting an instance of Entities\SimpleTable */
	public function TestGetInstance() {
		$this->SimpleTable = SimpleTable::N();
	}

	/** * — all fields of 1st layer */
	public function Test1A() {
		$fields = $this->SimpleTable->ExplainFields(['*']);
		//debug(var_export($fields, true));
		$awaits = [
			'ID'       => 'ID',
			'BOOLEAN'  => 'BOOLEAN',
			'INTEGER'  => 'INTEGER',
			'FLOAT'    => 'FLOAT',
			'STRING'   => 'STRING',
			'LINK'     => 'LINK',
			'TEXT'     => 'TEXT',
			'DATETIME' => 'DATETIME',
			'TIME'     => 'TIME',
			'DATE'     => 'DATE',
			'ENUM'     => 'ENUM',
			'SET'      => 'SET',
		];
		if ($fields <> $awaits) {
			throw new Exception($fields);
		}
	}

	/** @ — vital fields of 1st layer */
	public function Test1V() {
		$fields = $this->SimpleTable->ExplainFields(['@']);
		//debug(var_export($fields, true));
		$awaits = [
			'ID'     => 'ID',
			'STRING' => 'STRING',
		];
		if ($fields <> $awaits) {
			throw new Exception($fields);
		}
	}

	/** ** — all fields of 1st layer, all fields of 2nd layer */
	public function Test1A2A() {
		$fields = $this->SimpleTable->ExplainFields(['**']);
		//debug(var_export($fields, true));
		$awaits = [
			'ID'       => 'ID',
			'BOOLEAN'  => 'BOOLEAN',
			'INTEGER'  => 'INTEGER',
			'FLOAT'    => 'FLOAT',
			'STRING'   => 'STRING',
			'LINK'     => [
				'ID'       => 'ID',
				'BOOLEAN'  => 'BOOLEAN',
				'INTEGER'  => 'INTEGER',
				'FLOAT'    => 'FLOAT',
				'STRING'   => 'STRING',
				'LINK'     => 'LINK',
				'TEXT'     => 'TEXT',
				'DATETIME' => 'DATETIME',
				'TIME'     => 'TIME',
				'DATE'     => 'DATE',
				'ENUM'     => 'ENUM',
				'SET'      => 'SET',
			],
			'TEXT'     => 'TEXT',
			'DATETIME' => 'DATETIME',
			'TIME'     => 'TIME',
			'DATE'     => 'DATE',
			'ENUM'     => 'ENUM',
			'SET'      => 'SET',
		];
		if ($fields <> $awaits) {
			throw new Exception($fields);
		}
	}

	/** *@ — all fields of 1st layer, vital fields of 2nd layer */
	public function Test1A2V() {
		$fields = $this->SimpleTable->ExplainFields(['*@']);
		//debug(var_export($fields, true));
		$awaits = [
			'ID'       => 'ID',
			'BOOLEAN'  => 'BOOLEAN',
			'INTEGER'  => 'INTEGER',
			'FLOAT'    => 'FLOAT',
			'STRING'   => 'STRING',
			'LINK'     => [
				'ID'     => 'ID',
				'STRING' => 'STRING',
			],
			'TEXT'     => 'TEXT',
			'DATETIME' => 'DATETIME',
			'TIME'     => 'TIME',
			'DATE'     => 'DATE',
			'ENUM'     => 'ENUM',
			'SET'      => 'SET',
		];
		if ($fields <> $awaits) {
			throw new Exception($fields);
		}
	}

	/** complex selection */
	public function TestComplex() {
		$fields = $this->SimpleTable->ExplainFields([
			'@',
			'BOOLEAN',
			'LINK' => ['@', 'INTEGER'],
		]);
		//debug(var_export($fields, true));
		$awaits = [
			'ID'      => 'ID',
			'STRING'  => 'STRING',
			'BOOLEAN' => 'BOOLEAN',
			'LINK'    => [
				'ID'      => 'ID',
				'STRING'  => 'STRING',
				'INTEGER' => 'INTEGER',
			],
		];
		if ($fields <> $awaits) {
			throw new Exception($fields);
		}
	}
}