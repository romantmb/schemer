<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Tests;


function test(string $description, callable $fn): void
{
	echo $description . "\n";
	$fn();
}
