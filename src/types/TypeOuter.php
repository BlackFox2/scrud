<?php

namespace BlackFox2;

class TypeOuter extends Type {
	public static $TYPE = 'OUTER';

	public function FormatInputValue($value) {
		if (!is_numeric($value)) {
			throw new ExceptionType("Expected numerical value for '{$this->info['CODE']}', received: '{$value}'");
		}
		return (int)$value;
	}

	public function FormatOutputValue($element) {
		/** @var SCRUD $Link */
		$Link = $this->info['LINK'];
		$code = $this->info['CODE'];
		if (empty($Link)) {
			throw new ExceptionType("Field '{$code}': link must be specified");
		}
		if (!in_array('BlackFox2\SCRUD', class_parents($Link))) {
			throw new ExceptionType("Field '{$code}': link '{$Link}' must be SCRUD child");
		}
		$element[$code] = $Link::I()->FormatOutputValues($element[$code]);
		return $element;
	}

	public function PrepareSelectAndJoinByField($table, $prefix, $subfields) {
		if (empty($subfields)) {
			return parent::PrepareSelectAndJoinByField($table, $prefix, null);
		}
		$code = $this->info['CODE'];
		/** @var SCRUD $Link */
		$Link = $this->info['LINK']::I();
		$external_prefix = $prefix . $code . "__";
		$raw_link_code = $external_prefix . $Link->code;

		$join = "LEFT JOIN {$Link->code} AS {$raw_link_code} ON {$prefix}{$table}." . $this->Quote($code) . " = {$raw_link_code}." . $this->Quote($Link->key());
		$RESULT = $Link->PreparePartsByFields($subfields, $external_prefix);
		$RESULT['JOIN'] = array_merge([$raw_link_code => $join], $RESULT['JOIN']);
		return $RESULT;
	}

	public function GenerateJoinAndGroupStatements(SCRUD $Current, $prefix) {
		// debug($this->info, '$this->info');
		/** @var SCRUD $Target */
		$Target = $this->info['LINK']::I();

		$current_alias = $prefix . $Current->code;
		$current_key = $this->info['CODE'];
		$target_alias = $prefix . $this->info['CODE'] . '__' . $Target->code;
		$target_key = $Target->key();

		$statement = "LEFT JOIN {$Target->code} AS {$target_alias} ON {$current_alias}." . $this->Quote($current_key) . " = {$target_alias}." . $this->Quote($target_key);
		return [
			'JOIN'  => [$target_alias => $statement],
			'GROUP' => [],
		];
	}

}