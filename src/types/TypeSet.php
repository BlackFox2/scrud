<?php

namespace BlackFox2;

class TypeSet extends Type {
	public static $TYPE = 'SET';

	public function FormatInputValue($values) {
		if (!is_array($values)) {
			$values = [$values];
		}
		foreach ($values as $value) {
			if (!isset($this->info['VALUES'][$value])) {
				throw new ExceptionType("Unknown set value '{$value}' for field '{$this->info['NAME']}'");
			}
		}
		$value = implode(',', $values);
		return $value;
	}

	public function FormatOutputValue($element) {
		$code = $this->info['CODE'];
		if (empty($element[$code])) {
			$element[$code] = [];
		} else {
			$element[$code] = explode(",", $element[$code]);
		}
		$element["$code|VALUES"] = [];
		foreach ($element["$code"] as $key) {
			$element["$code|VALUES"][$key] = $this->info['VALUES'][$key];
		}
		return $element;
	}
}