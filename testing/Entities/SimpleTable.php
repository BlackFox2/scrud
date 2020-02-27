<?php

namespace BlackFox2;

class SimpleTable extends SCRUD {

	public $name = 'Simple Table';

	public $fields = [
		'ID'       => self::ID,
		'BOOLEAN'  => [
			'TYPE' => 'BOOLEAN',
			'NAME' => 'Bool',
		],
		'INTEGER'  => [
			'TYPE' => 'INTEGER',
			'NAME' => 'Number',
		],
		'FLOAT'    => [
			'TYPE' => 'FLOAT',
			'NAME' => 'Float',
		],
		'STRING'   => [
			'TYPE'  => 'STRING',
			'NAME'  => 'String',
			'VITAL' => true,
		],
		'LINK'     => [
			'TYPE' => 'OUTER',
			'NAME' => 'Link to self',
			'LINK' => 'SimpleTable',
		],
		'TEXT'     => [
			'TYPE' => 'TEXT',
			'NAME' => 'Text',
		],
		'DATETIME' => [
			'TYPE' => 'DATETIME',
			'NAME' => 'Datetime',
		],
		'TIME'     => [
			'TYPE' => 'TIME',
			'NAME' => 'Time',
		],
		'DATE'     => [
			'TYPE' => 'DATE',
			'NAME' => 'Date',
		],
		'ENUM'     => [
			'TYPE'   => 'ENUM',
			'NAME'   => 'Enum',
			'VALUES' => [
				'VALUE_1' => 'Value one',
				'VALUE_2' => 'Value two',
				'VALUE_3' => 'Value three',
			],
		],
		'SET'      => [
			'TYPE'   => 'SET',
			'NAME'   => 'Set',
			'VALUES' => [
				'VALUE_4' => 'Value four',
				'VALUE_5' => 'Value five',
				'VALUE_6' => 'Value six',
				'VALUE_7' => 'Value seven',
			],
		],
	];

}