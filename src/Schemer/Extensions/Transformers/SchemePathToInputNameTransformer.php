<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Transformers;

use Schemer\Support\StaticClass;
use Tuupola\Base58;


class SchemePathToInputNameTransformer extends StaticClass
{
	/** @const string */
	const INPUT_PREFIX = 'scheme__' ;


	/**
	 * @param string $nodePath
	 * @return string|null
	 */
	public static function transform(string $nodePath): ?string
	{
		return self::INPUT_PREFIX . (new Base58)->encode($nodePath);
	}
}
