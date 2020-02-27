<?php

namespace BlackFox2;

class TypeEnum extends Type {
	public static $TYPE = 'ENUM';

	public function FormatInputValue($value) {
		if (!isset($this->info['VALUES'][$value])) {
			throw new ExceptionType("Unknown enum value '{$value}' for field '{$this->info['NAME']}'");
		}
		return $value;
	}

	public function FormatOutputValue($element) {
		$code = $this->info['CODE'];
		$element["$code|VALUE"] = $this->info['VALUES'][$element[$code]];
		return $element;
	}

}