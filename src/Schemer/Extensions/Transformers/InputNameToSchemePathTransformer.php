<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Transformers;

use Nette\Utils\Strings;
use Tuupola\Base58;


class InputNameToSchemePathTransformer
{

	private function __construct() {}


	public static function transform(?string $name = null): ?string
	{
		return str_starts_with($name, self::prefix())
			? (new Base58)->decode(self::strip($name))
			: null;
	}


	private static function prefix(): string
	{
		return SchemePathToInputNameTransformer::prefix();
	}


	private static function strip(string $name): string
	{
		return Strings::replace($name, sprintf('/^%s/', self::prefix()));
	}
}
