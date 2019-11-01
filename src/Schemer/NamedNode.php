<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;


/**
 * Node with name, e.g. "item": {}
 */
interface NamedNode
{
	/**
	 * @return string
	 */
	public function getName(): string;


	/**
	 * @return string
	 */
	public function getPath(): string;
}
