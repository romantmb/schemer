<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Support;


class Helpers
{

	/**
	 * Exports value (as well as array of values) into string representation
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function export($value): string
	{
		if (is_array($value)) {
			return implode(', ', array_map(function($val) {
				return self::export($val);
			}, $value));
		}

		$export = var_export($value, true);

		return is_null($value) ? strtolower($export) : $export;
	}
}
