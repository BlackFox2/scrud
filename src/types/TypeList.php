<?php

namespace BlackFox2;

class TypeList extends TypeText {
	public static $TYPE = 'LIST';

	public function FormatInputValue($value) {
		$value = is_array($value) ? $value : [$value];
		$value = array_filter($value, 'strlen');
		$value = json_encode($value, JSON_UNESCAPED_UNICODE);
		return parent::FormatInputValue($value);
	}

	public function FormatOutputValue($element) {
		$code = $this->info['CODE'];
		$element[$code] = json_decode($element[$code], true);
		if (json_last_error()) {
			$element[$code] = [];
		}
		return $element;
	}

}