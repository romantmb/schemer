<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Support;

use Schemer\Exceptions\StaticClassException;


abstract class StaticClass
{
	public function __construct()
	{
		throw new StaticClassException(sprintf('Methods in %s are for static call only.', static::class));
	}
}
