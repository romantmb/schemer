<?php

/**
 * Schemer
 * @author Roman Pistek
 */

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
	 * @param mixed ...$content
	 * @return Group
	 */
	function group(...$content): Group
	{
		return Scheme::group(...$content);
	}
}
