<?php

namespace BlackFox2;

class TypeDate extends Type {
	public static $TYPE = 'DATE';

	public function FormatInputValue($value) {
		$value = is_numeric($value) ? $value : strtotime($value);
		$value = date('Y-m-d', $value);
		return $value;
	}

	public function FormatOutputValue($element) {
		$code = $this->info['CODE'];
		$element[$code . '|TIMESTAMP'] = strtotime($element[$code]);
		return $element;
	}

}
