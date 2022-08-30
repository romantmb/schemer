<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Tests;

use Schemer\Node;


function test(string $description, callable $fn): void
{
	echo $description . "\n";
	$fn();
}


function json(Node $node): string
{
	return str_replace("\n", "\r\n", $node->toJson(JSON_PRETTY_PRINT));
}
