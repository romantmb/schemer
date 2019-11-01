<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Extensions\Transformers;

use Nette\Utils\Strings;
use Nette\StaticClassException;
use Tuupola\Base58;


class InputNameToSchemePathTransformer
{

	public function __construct()
	{
		throw new StaticClassException(sprintf('Methods in %s are for static call only.', self::class));
	}


	/**
	 * @param string|null $name
	 * @return string|null
	 */
	public static function transform(string $name = null): ?string
	{
		if (!$name || strpos($name, SchemePathToInputNameTransformer::INPUT_PREFIX) !== 0) {
			return null;
		}

		return (new Base58)->decode(Strings::replace($name, sprintf('/^%s/', SchemePathToInputNameTransformer::INPUT_PREFIX)));
	}
}
