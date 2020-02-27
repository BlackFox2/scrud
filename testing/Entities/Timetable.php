<?php

namespace BlackFox2;

class Timetable extends SCRUD {
	public function Init() {
		$this->name = 'Timetable';
		$this->fields = [
			'ID'       => self::ID,
			'ROOM'     => [
				'TYPE'     => 'OUTER',
				'NAME'     => 'Room',
				'LINK'     => 'Rooms',
				'NOT_NULL' => true,
				'FOREIGN'  => 'CASCADE',
			],
			'GRADE'    => [
				'TYPE'     => 'OUTER',
				'NAME'     => 'Grade',
				'LINK'     => 'Grades',
				'NOT_NULL' => true,
				'FOREIGN'  => 'CASCADE',
			],
			'START'    => [
				'TYPE'     => 'DATETIME',
				'NAME'     => 'Class start time',
				'NOT_NULL' => true,
				'VITAL'    => true,
			],
			'DURATION' => [
				'TYPE'     => 'INTEGER',
				'NAME'     => 'Duration (in hours)',
				'NOT_NULL' => true,
				'DEFAULT'  => 1,
			],
		];
	}

	public function Fill($total) {
		// todo move inside the test
		$grade_ids = Grades::N()->GetColumn();
		$rooms_ids = Rooms::N()->GetColumn();
		for ($i = 0; $i < $total; $i++) {
			$this->Create([
				'GRADE' => $grade_ids[array_rand($grade_ids)],
				'ROOM'  => $rooms_ids[array_rand($rooms_ids)],
				'START' => time() + $i * 3600,
			]);
		}
	}
}

