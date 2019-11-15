<?php

/**
 * Schemer
 * @author Roman Pistek
 */

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
	public function setProperty(Property $property);


	/**
	 * @return Property|null
	 */
	public function getProperty(): ?Property;
}
