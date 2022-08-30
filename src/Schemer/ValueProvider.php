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

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function setValue($value);


	/**
	 * @return mixed
	 */
	public function getValue();


	/**
	 * @param  Property $property
	 * @return ValueProvider
	 */
	public function setProperty(Property $property): ValueProvider;


	/**
	 * @return Property|null
	 */
	public function getProperty(): ?Property;
}
