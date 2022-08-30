<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer
{

	/**
	 * @param mixed ...$nodes
	 * @return Node
	 */
	function bag(...$nodes): Node
	{
		return Scheme::bag(...$nodes);
	}


	/**
	 * @param string $name
	 * @param mixed  ...$content
	 * @return Property
	 */
	function property(string $name, ...$content): Property
	{
		return Scheme::prop($name, ...$content);
	}


	/**
	 * @param string $name
	 * @param mixed  ...$content
	 * @return Options
	 */
	function candidates(string $name, ...$content): Options
	{
		return Scheme::candidates($name, ...$content);
	}


	/**
	 * @param string $name
	 * @param mixed  ...$content
	 * @return Options
	 */
	function options(string $name, ...$content): Options
	{
		return Scheme::options($name, ...$content);
	}


	/**
	 * @param mixed ...$content
	 * @return Group
	 */
	function group(...$content): Group
	{
		return Scheme::group(...$content);
	}
}
