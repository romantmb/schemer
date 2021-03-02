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
			return implode(', ', array_map(static function($val) {
				return self::export($val);
			}, $value));
		}

		$export = var_export($value, true);

		return is_null($value) ? strtolower($export) : $export;
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public static function sanitizeValue($value)
	{
		if (is_array($value)) {
			return collect($value)
				->mapWithKeys(static function($v, $k) {
					return [ $k => self::sanitizeValue($v) ];
				})
				->all();
		}

		if (! is_string($value)) {
			return $value;
		}

		if (is_numeric($value)) {
			return strpos($value, '.') ? (float) $value : (int) $value;
		}

		if ($value === 'null' || $value === '') {
			return null;
		}

		if (in_array($value, [ 'true', 'false' ])) {
			return $value === 'true';
		}

		return $value;
	}
}
