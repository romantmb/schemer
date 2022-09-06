<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Support;


class Strings extends \Nette\Utils\Strings
{

	/**
	 * Converts snake_case to camelCase (i.e. 'hello_world' -> 'helloWorld')
	 */
	public static function toCamelCase(string $str, string $sep = '_'): string
	{
		if (! str_contains($str, $sep)) {
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
	 * Converts camelCase to snake_case (i.e. 'helloWorld' -> 'hello_world')
	 */
	public static function toSnakeCase(string $str, string $sep = '_'): string
	{
		return parent::lower(
			parent::replace($str, '~(?<!^)[A-Z]~', sprintf('%s\\0', $sep))
		);
	}
}
