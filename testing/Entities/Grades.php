<?php

namespace BlackFox2;

class Grades extends SCRUD {

	public function Init() {
		$this->name = 'Grades';
		$this->fields = [
			'ID'         => self::ID,
			'TITLE'      => [
				'TYPE'  => 'STRING',
				'NAME'  => 'Title',
				'VITAL' => true,
			],
			'CAPTAIN'    => [
				'NAME' => 'Captain',
				'TYPE' => 'OUTER',
				'LINK' => 'Students',
			],
			'STUDENTS'   => [
				'NAME'  => 'Students',
				'TYPE'  => 'INNER',
				'LINK'  => 'Students',
				'FIELD' => 'GRADE',
			],
			'TIMETABLES' => [
				'NAME'  => 'Timetable',
				'TYPE'  => 'INNER',
				'LINK'  => 'Timetable',
				'FIELD' => 'GRADE',
			],
		];
	}

	public function Fill() {
		// todo move inside the test
		foreach (['A', 'B', 'C'] as $class_letter) {
			foreach ([1, 2, 3, 4, 5, 7, 8, 9, 10, 11] as $class_number) {
				$this->Create([
					'TITLE' => $class_number . $class_letter,
				]);
			}
		}
	}

	public function FillCaptains() {
		// todo move inside the test
		$grade_ids = $this->GetColumn();
		$students_ids = Students::I()->GetColumn();
		foreach ($grade_ids as $grade_id) {
			$this->Update($grade_id, ['CAPTAIN' => $students_ids[array_rand($students_ids)]]);
		}
	}
}