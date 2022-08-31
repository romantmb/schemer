<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Support;

use Schemer\Exceptions\StaticClassException;


/**
 * @deprecated
 */
abstract class StaticClass
{
	public function __construct()
	{
		throw new StaticClassException(sprintf('Methods in %s are for static call only.', static::class));
	}
}
