<?php

namespace BlackFox2;

class TypeInteger extends Type {
	public static $TYPE = 'NUMBER';

	public function FormatInputValue($value) {
		if (!is_numeric($value)) {
			throw new ExceptionType("Expected numerical value for '{$this->info['CODE']}', received: '{$value}'");
		}
		return (int)$value;
	}

	public function FormatOutputValue($element) {
		return $element;
		// TODO convert to integer (if not null)
		// $element[$this->info['CODE']] = (int)$element[$this->info['CODE']];
	}
}