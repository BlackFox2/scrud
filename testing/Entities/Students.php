<?php

namespace BlackFox2;

class Students extends SCRUD {

	public $name = 'Students';

	public $fields = [
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