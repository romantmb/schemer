<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;


/**
 * Dynamic value provider
 */
interface ValueProvider
{

	public function setValue($value);


	public function getValue();


	public function setProperty(Property $property): ValueProvider;


	public function getProperty(): ?Property;
}
