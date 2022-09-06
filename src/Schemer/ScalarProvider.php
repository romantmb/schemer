<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;


final class ScalarProvider implements ValueProvider
{
	private ?Property $property = null;


	public function __construct(private mixed $value)
	{
	}


	public function setValue($value): mixed
	{
		return $this->value = $value;
	}


	public function getValue(): mixed
	{
		return $this->value;
	}


	public function setProperty(Property $property): ScalarProvider
	{
		$this->property = $property;
		return $this;
	}


	public function getProperty(): ?Property
	{
		return $this->property;
	}
}
