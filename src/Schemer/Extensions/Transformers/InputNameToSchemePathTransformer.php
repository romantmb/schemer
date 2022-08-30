<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Transformers;

use Schemer\Support\StaticClass;
use Nette\Utils\Strings;
use Tuupola\Base58;


class InputNameToSchemePathTransformer extends StaticClass
{

	/**
	 * @param string|null $name
	 * @return string|null
	 */
	public static function transform(string $name = null): ?string
	{
		if (! $name || strpos($name, SchemePathToInputNameTransformer::INPUT_PREFIX) !== 0) {
			return null;
		}

		return (new Base58)->decode(Strings::replace($name, sprintf('/^%s/', SchemePathToInputNameTransformer::INPUT_PREFIX)));
	}
}
