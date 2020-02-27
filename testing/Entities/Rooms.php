<?php

namespace BlackFox2;

class Rooms extends SCRUD {

	public function Init() {
		$this->name = 'Rooms';
		$this->fields = [
			'ID'    => self::ID,
			'TITLE' => [
				'TYPE'     => 'STRING',
				'NAME'     => 'Room number',
				'NOT_NULL' => true,
				'VITAL'    => true,
			],
		];
	}

	public function Fill() {
		// todo move inside the test
		$rooms = [101, 102, 103, 104, 105, 106, 107, 201, 203, 205, 207, 209, 301, 304, 307, 311];
		foreach ($rooms as $room) {
			$this->Create(['TITLE' => 'R-' . $room]);
		}
	}

}

