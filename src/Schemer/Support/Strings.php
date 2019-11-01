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
	 * Converts snake_case to camelCase (i.e. 'hello_world' -> 'helloWorld')
	 *
	 * @param  string $str
	 * @param  string $sep
	 * @return string
	 */
	public static function toCamelCase($str, $sep = '_')
	{
		if (strpos($str, $sep) === false) {
			return $str;
		}
		return parent::firstLower(
			str_replace(' ', '',
				parent::capitalize(
					strtr($str, $sep, ' ')
				)
			)
		);
	}


	/**
	 * Converts camelCase to snake_case (i.e. 'helloWorld' -> 'hello_world')
	 *
	 * @param  string $str
	 * @param  string $sep
	 * @return string
	 * @throws InvalidStateException
	 */
	public static function toSnakeCase($str, $sep = '_')
	{
		return parent::lower(
			parent::replace($str, '~(\w)([A-Z])~', sprintf('\\1%s\\2', $sep))
		);
	}
}
