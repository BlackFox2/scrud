<?php

namespace BlackFox2;

class Students extends SCRUD {

	public function Init() {
		$this->name = 'Students';
		$this->fields = [
			'ID'         => self::ID,
			'FIRST_NAME' => [
				'TYPE'  => 'STRING',
				'NAME'  => 'First name',
				'VITAL' => true,
			],
			'LAST_NAME'  => [
				'TYPE' => 'STRING',
				'NAME' => 'Last name',
			],
			'GRADE'      => [
				'TYPE'    => 'OUTER',
				'LINK'    => 'Grades',
				'NAME'    => 'Grade',
				'FOREIGN' => 'RESTRICT',
			],
		];
	}

	public function Fill($total) {
		// todo move inside the test
		$names = file(__DIR__ . '/data/names.txt', FILE_IGNORE_NEW_LINES);
		$lasts = ['J', 'G', 'V', 'X', 'Z'];
		$grade_ids = Grades::N()->GetColumn();
		if(empty($grade_ids)) {
			throw new \Exception("No grades has been found");
		}
		for ($i = 0; $i < $total; $i++) {
			$this->Create([
				'FIRST_NAME' => $names[array_rand($names)],
				'LAST_NAME'  => $lasts[array_rand($lasts)] . '.',
				'GRADE'      => $grade_ids[array_rand($grade_ids)],
			]);
		}
	}
}