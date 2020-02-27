<?php

namespace BlackFox2;

class FactoryType {

	use Instance;

	public static $TYPES = [
		'ARRAY'    => '\BlackFox2\TypeArray',
		'BOOLEAN'  => '\BlackFox2\TypeBoolean',
		'DATE'     => '\BlackFox2\TypeDate',
		'DATETIME' => '\BlackFox2\TypeDateTime',
		'ENUM'     => '\BlackFox2\TypeEnum',
		'FLOAT'    => '\BlackFox2\TypeFloat',
		'INNER'    => '\BlackFox2\TypeInner',
		'LIST'     => '\BlackFox2\TypeList',
		'INTEGER'  => '\BlackFox2\TypeInteger',
		'OUTER'    => '\BlackFox2\TypeOuter',
		'PASSWORD' => '\BlackFox2\TypePassword',
		'SET'      => '\BlackFox2\TypeSet',
		'STRING'   => '\BlackFox2\TypeString',
		'TEXT'     => '\BlackFox2\TypeText',
		'TIME'     => '\BlackFox2\TypeTime',
	];

	public static function Add($name, $class) {
		self::$TYPES[$name] = $class;
	}

	/**
	 * Get instance of class mapped to code of the type
	 *
	 * @param array $info info of the field
	 * @param Database $Database
	 * @return Type instance of class
	 * @throws Exception
	 */
	public static function Get(array $info, Database $Database) {
		$info['TYPE'] = strtoupper($info['TYPE']);
		if (!isset(self::$TYPES[$info['TYPE']])) {
			throw new Exception("Class for type '{$info['TYPE']}' not found, field code: '{$info['CODE']}'");
		}
		/** @var Type $class */
		$class = self::$TYPES[$info['TYPE']];
		return new $class($info, $Database);
	}

}