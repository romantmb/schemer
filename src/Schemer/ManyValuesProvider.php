<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;


/**
 * Value provider
 */
interface ManyValuesProvider extends ValueProvider
{

	public function getValues(): array;


	public function preserveKeys(): bool;


	public function multipleValues(): bool;


	public function setProperty(Property $property): ManyValuesProvider;
}
