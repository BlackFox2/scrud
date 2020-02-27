<?php

namespace BlackFox2;

class TypeBoolean extends Type {
	public static $TYPE = 'BOOL';

	public function FormatOutputValue($element) {
		$value = &$element[$this->info['CODE']];
		if ($value === 'f') {
			$value = false;
		} else {
			$value = (bool)$value;
		}
		return $element;
	}

	public function FormatInputValue($value) {
		return $value ? 1 : 0;
	}

	public function ProvideInfoIntegrity() {
		$this->info['NOT_NULL'] = true;
		$this->info['DEFAULT'] = (bool)($this->info['DEFAULT'] ?: false);
	}

}