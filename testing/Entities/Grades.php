<?php

namespace BlackFox2;

class Grades extends SCRUD {

	public $name = 'Grades';

	public $fields = [
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