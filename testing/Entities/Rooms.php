<?php

namespace BlackFox2;

class Rooms extends SCRUD {

	public $name = 'Rooms';

	public $fields = [
		'ID'    => self::ID,
		'TITLE' => [
			'TYPE'     => 'STRING',
			'NAME'     => 'Room number',
			'NOT_NULL' => true,
			'VITAL'    => true,
		],
	];

}

