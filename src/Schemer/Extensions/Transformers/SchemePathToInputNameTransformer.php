<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Extensions\Transformers;

use Nette\StaticClassException;
use Tuupola\Base58;


class SchemePathToInputNameTransformer
{
	/** @const string */
	const INPUT_PREFIX = 'scheme__' ;


	public function __construct()
	{
		throw new StaticClassException(sprintf('Methods in %s are for static call only.', self::class));
	}


	/**
	 * @param string $nodePath
	 * @return string|null
	 */
	public static function transform(string $nodePath): ?string
	{
		return self::INPUT_PREFIX . (new Base58)->encode($nodePath);
	}
}
