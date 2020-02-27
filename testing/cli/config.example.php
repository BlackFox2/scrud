<?php
$Connections = [
	'MySQL'    => new BlackFox2\MySQL([
		'DATABASE' => 'blackfox2',
		'USER'     => 'root',
		'PASSWORD' => '',
		'HOST'     => 'localhost',
	]),
	'Postgres' => new BlackFox2\Postgres([
		'DATABASE' => 'blackfox2',
		'USER'     => 'postgres',
		'PASSWORD' => '',
		'HOST'     => 'localhost',
		'PORT'     => 5432,
	]),
];

$Tests = [
	BlackFox2\Test_SCRUD_SimpleTable::class,
	BlackFox2\Test_SCRUD_ExplainFields::class,
	BlackFox2\Test_SCRUD_Links::class,
];