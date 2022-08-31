<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Transformers;

use Tuupola\Base58;


final class SchemePathToInputNameTransformer
{

	private function __construct() {}


	public static function transform(string $nodePath): ?string
	{
		return self::prefix() . (new Base58)->encode($nodePath);
	}


	public static function prefix(): string
	{
		return 'scheme__';
	}
}
