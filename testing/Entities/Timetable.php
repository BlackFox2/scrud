<?php

namespace BlackFox2;

class Timetable extends SCRUD {

	public $name = 'Timetable';

	public $fields = [
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

