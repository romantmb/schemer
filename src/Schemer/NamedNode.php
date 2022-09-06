<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;


/**
 * Node with name, e.g. "item": {}
 */
interface NamedNode
{

	public function getName(): string;


	public function getPath(): string;
}
