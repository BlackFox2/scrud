<?php

namespace BlackFox2;

class Test_SCRUD_SimpleTable extends Test {

	public $name = 'SCRUD: Base methods';
	public $limit = 25;

	/** @var SimpleTable */
	private $SimpleTable;

	/** Getting an instance of Entities\SimpleTable */
	public function TestGetInstance() {
		$this->SimpleTable = SimpleTable::N();
	}

	/** fields synchronization */
	public function TestSynchronize() {
		$Schema = Schema::N();
		$Schema->setTables([$this->SimpleTable]);
		$Schema->synchronize();
	}

	/** Deleting of all records */
	public function TestTruncate() {
		$this->SimpleTable->Truncate();
		$rows = $this->SimpleTable->Select();
		if (!empty($rows)) {
			throw new Exception('There are records in the table');
		}
	}

	/** Creating random records */
	public function TestCreateRandomRows() {
		$R = [];
		for ($i = 0; $i < $this->limit; $i++) {
			$R[] = $this->SimpleTable->Create([
				'BOOLEAN'  => array_rand([true, false]),
				'INTEGER'  => random_int(0, 99),
				'FLOAT'    => random_int(0, 99999) / random_int(1, 9),
				'STRING'   => sha1(random_bytes(8)),
				'LINK'     => @$R[array_rand($R)] ?: null,
				'TEXT'     => str_repeat(sha1(random_bytes(8)) . ' ', 2),
				'DATETIME' => time() + random_int(-99999, 99999),
				'TIME'     => random_int(0, 23) . ':' . random_int(0, 59),
				'DATE'     => '+' . random_int(2, 90) . ' days',
				'ENUM'     => array_rand($this->SimpleTable->fields['ENUM']['VALUES']),
				'SET'      => array_rand($this->SimpleTable->fields['SET']['VALUES'], rand(1, count($this->SimpleTable->fields['SET']['VALUES']))),
			]);
		}
		return $R;
	}

	/** Attempt to create an incorrect entry with a field of type ENUM */
	public function TestCreateBadRow() {
		try {
			$this->SimpleTable->Create(['ENUM' => 'BAD_VALUE']);
		} catch (\Exception $error) {
			return $error->getMessage();
		}
		throw new Exception('An error was expected when trying to insert an unknown value in the ENUM type field');
	}

	/** Attempt to incorrectly update a field of type ENUM */
	public function TestUpdateBadRow() {
		try {
			$this->SimpleTable->Update(1, ['ENUM' => 'BAD_VALUE']);
		} catch (\Exception $error) {
			return $error->getMessage();
		}
		throw new Exception('An error was expected while trying to update an unknown value in the ENUM type field');
	}

	/** Attempt to correctly update a field of type ENUM */
	public function TestUpdateGoodRow() {
		$this->SimpleTable->Update(1, ['ENUM' => 'VALUE_2']);
	}

	/** Filter check: boolean value */
	public function TestFilterByBool() {
		foreach ([true, false] as $value) {
			$elements = $this->SimpleTable->Select([
				'FILTER' => ['BOOLEAN' => $value],
				'FIELDS' => ['ID', 'BOOLEAN'],
			]);
			foreach ($elements as $id => $element) {
				if ($element['BOOLEAN'] <> $value) {
					throw new Exception("Element #{$id}: value BOOLEAN: {$value} <> {$element['BOOLEAN']}");
				}
			}
		}
	}

	/** Filter check: integer value */
	public function TestFilterByNumber() {
		$value = rand(0, 99);
		$elements = $this->SimpleTable->Select([
			'FILTER' => ['INTEGER' => $value],
			'FIELDS' => ['ID', 'INTEGER'],
		]);
		foreach ($elements as $id => $element) {
			if ($element['INTEGER'] <> $value) {
				throw new Exception("Element #{$id}: value INTEGER <> {$value}");
			}
		}
		return count($elements);
	}

	private function getRandomString() {
		$max = $this->SimpleTable->GetCell([], 'ID', ['ID' => 'DESC']);
		$min = $this->SimpleTable->GetCell([], 'ID', ['ID' => 'ASC']);
		return $this->SimpleTable->GetCell(rand($min, $max), 'STRING');
	}

	/** Filter check: string value */
	public function TestFilterByString() {
		$random_string = $this->getRandomString();
		$elements = $this->SimpleTable->Select([
			'FILTER' => ['STRING' => $random_string],
			'FIELDS' => ['ID', 'STRING'],
		]);
		foreach ($elements as $id => $element) {
			if ($element['STRING'] <> $random_string) {
				throw new Exception("Element #{$id}: value STRING <> {$random_string}");
			}
		}
		return $random_string;
	}

	/** Filter check: substring value */
	public function TestFilterBySubString() {
		$random_string = $this->getRandomString();
		$random_string = substr($random_string, rand(1, 3), rand(3, 5));
		if (empty($random_string)) {
			throw new Exception('Could not retrieve random substring');
		}
		$elements = $this->SimpleTable->Select([
			'FILTER' => ['~STRING' => $random_string],
			'FIELDS' => ['ID', 'STRING'],
		]);
		foreach ($elements as $id => $element) {
			if (strpos($element['STRING'], $random_string) === false) {
				throw new Exception("Element #{$id}: value STRING: '{$random_string}' <> '{$element['STRING']}'");
			}
		}
		return $random_string;
	}

	/** Filter check: approximate date */
	public function TestFilterDateApproximate() {
		$values = $this->SimpleTable->GetColumn(['~DATETIME' => date('d.m.Y')], 'DATETIME');
		foreach ($values as $raw) {
			$date = date('d.m.Y', strtotime($raw));
			if ($date <> date('d.m.Y')) {
				throw new Exception("Wrong date: {$date}");
			}
		}
		// return [$this->SCRUD->SQL, $values];
	}

	/** Pager check: no filtering */
	public function TestPager() {
		$step = 10;
		foreach ([1, 2, 3] as $page) {
			$result = $this->SimpleTable->Search([
				'LIMIT'  => $step,
				'PAGE'   => $page,
				'FIELDS' => ['ID'],
			]);
			$expected_pager = [
				'TOTAL'    => $this->limit,
				'CURRENT'  => $page,
				'LIMIT'    => $step,
				'SELECTED' => max(0, min($this->limit - ($page - 1) * $step, $step)),
			];
			if ($result['PAGER'] <> $expected_pager) {
				throw new Exception(["Unexpected PAGER", $expected_pager, $result['PAGER'], $this->SimpleTable->SQL]);
			}
		}
	}

	/** Count check */
	public function TestCount() {
		$count[1] = $this->SimpleTable->Count([]);
		$count[2] = $this->SimpleTable->Count(['BOOLEAN' => true]);
		$count[3] = $this->SimpleTable->Count(['BOOLEAN' => false]);
		if ($count[1] <> $count[2] + $count[3]) {
			throw new Exception("Unexpected checksum");
		}
		return "{$count[1]} = {$count[2]} + {$count[3]}";
	}
}