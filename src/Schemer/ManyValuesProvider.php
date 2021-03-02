<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;


/**
 * Value provider
 */
interface ManyValuesProvider extends ValueProvider
{
	/**
	 * @return array
	 */
	public function getValues(): array;


	/**
	 * @return bool
	 */
	public function preserveKeys(): bool;


	/**
	 * @return bool
	 */
	public function multipleValues(): bool;


	/**
	 * @param  Property $property
	 * @return ManyValuesProvider
	 */
	public function setProperty(Property $property): ValueProvider;
}
