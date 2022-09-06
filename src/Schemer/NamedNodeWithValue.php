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

	public function getValue(): mixed;


	public function setValue(mixed $value): NamedNodeWithValue;
}
