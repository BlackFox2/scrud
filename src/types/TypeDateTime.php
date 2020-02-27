<?php

namespace BlackFox2;

class TypeDateTime extends Type {
	public static $TYPE = 'DATETIME';

	public function FormatInputValue($value) {
		if (is_numeric($value)) {
			$value = date('Y-m-d H:i:s', $value);
		} else {
			$value = date('Y-m-d H:i:s', strtotime($value));
		}
		return $value;
	}

	public function FormatOutputValue($element) {
		$code = $this->info['CODE'];
		$element[$code . '|TIMESTAMP'] = strtotime($element[$code]);
		return $element;
	}

	/*
	 * // TODO replicate somewhere ?
	public function GetStructureString() {
		$string = parent::GetStructureString();
		if ($this->info['TRIGGER'] === 'CREATE') {
			$string = "{$string} DEFAULT CURRENT_TIMESTAMP";
		}
		if ($this->info['TRIGGER'] === 'UPDATE') {
			$string = "{$string} ON UPDATE CURRENT_TIMESTAMP";
		}
		return $string;
	}
	*/

	public function PrepareConditions($table, $operator, $values) {
		if ($operator === '~') {
			$code = $this->info['CODE'];
			$data = date('Y-m-d', strtotime($values));
			$condition = "DATE({$table}." . $this->Quote($code) . ") = '{$data}'";
			return ['~' => $condition];
		}
		return parent::PrepareConditions($table, $operator, $values);
	}

}