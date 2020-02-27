<?php

namespace BlackFox2;

class TypeString extends Type {
	public static $TYPE = 'STRING';
	const DEFAULT_LENGTH = 255;

	/**
	 * Deleting all extra spaces
	 *
	 * @param string $value
	 * @return string
	 */
	public function FormatInputValue($value) {
		return trim(mb_ereg_replace('#\s+#', ' ', $value));
	}

}