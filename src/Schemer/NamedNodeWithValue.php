<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;


/**
 * Node with name with value, e.g. "item": 123
 */
interface NamedNodeWithValue extends NamedNode
{
	/**
	 * @return mixed|null
	 */
	public function getValue();


	/**
	 * @param  mixed $value
	 * @return NamedNodeWithValue
	 */
	public function setValue($value): NamedNodeWithValue;
}
