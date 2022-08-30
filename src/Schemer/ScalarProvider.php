<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;


final class ScalarProvider implements ValueProvider
{
	/** @var mixed */
	private $value;

	/** @var Property */
	private $property;


	/**
	 * @param mixed $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function setValue($value)
	{
		return $this->value = $value;
	}


	/**
	 * @return mixed|null
	 */
	public function getValue()
	{
		return $this->value;
	}


	/**
	 * @param  Property $property
	 * @return ValueProvider
	 */
	public function setProperty(Property $property): ValueProvider
	{
		$this->property = $property;

		return $this;
	}


	/**
	 * @return Property|null
	 */
	public function getProperty(): ?Property
	{
		return $this->property;
	}
}
