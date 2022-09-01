<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer
{

	function bag(...$nodes): Node
	{
		return Scheme::bag(...$nodes);
	}


	function property(string $name, ...$content): Property
	{
		return Scheme::prop($name, ...$content);
	}


	function candidates(string $name, ...$content): Options
	{
		return Scheme::candidates($name, ...$content);
	}


	function options(string $name, ...$content): Options
	{
		return Scheme::options($name, ...$content);
	}


	function group(...$content): Group
	{
		return Scheme::group(...$content);
	}


	// as short as possible (for those who know it well)

	/** bag */
	function b(...$nodes): Node { return bag(...$nodes); }

	/** property */
	function p(string $name, ...$content): Property { return property($name, ...$content); }

	/** candidates */
	function c(string $name, ...$content): Options { return candidates($name, ...$content); }

	/** options */
	function o(string $name, ...$content): Options { return options($name, ...$content); }

	/** group */
	function g(...$content): Group { return group(...$content); }
}
