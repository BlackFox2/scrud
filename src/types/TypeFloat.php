<?php

namespace BlackFox2;

class TypeFloat extends Type {
	public static $TYPE = 'FLOAT';

	public function FormatInputValue($value) {
		return str_replace(',', '.', (float)$value);
	}

}