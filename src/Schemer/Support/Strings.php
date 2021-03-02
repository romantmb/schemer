<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Support;

use Nette\Utils\Strings as NetteStrings;
use Nette\InvalidStateException;


class Strings extends NetteStrings
{

	/**
	 * Converts snake_case to camelCase (e.g. 'hello_world' -> 'helloWorld')
	 *
	 * @param string $str
	 * @param string $sep
	 * @return string
	 */
	public static function toCamelCase(string $str, string $sep = '_'): string
	{
		if (strpos($str, $sep) === false) {
			return $str;
		}

		return parent::firstLower(
			str_replace(' ', '',
				parent::capitalize(
					str_replace($sep, ' ', $str)
				)
			)
		);
	}


	/**
	 * Converts camelCase to snake_case (e.g. 'helloWorld' -> 'hello_world')
	 *
	 * @param string $str
	 * @param string $sep
	 * @return string
	 * @throws InvalidStateException
	 */
	public static function toSnakeCase(string $str, string $sep = '_'): string
	{
		return parent::lower(
			parent::replace($str, '~(\w)([A-Z])~', sprintf('\\1%s\\2', $sep))
		);
	}
}
