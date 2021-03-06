<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;

use Schemer\Support\Helpers;
use Schemer\Exceptions\InvalidValueException;
use Nette\Utils\Arrays;


class StaticArrayProvider implements ManyValuesProvider
{
	/** @var array */
	private $values;

	/** @var Property */
	private $property;


	/**
	 * @param array $optionalValues
	 */
	public function __construct(array $optionalValues)
	{
		if (Arrays::isList($optionalValues)) {
			$optionalValues = array_combine($optionalValues, $optionalValues);
		}

		$this->values = $optionalValues;
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 * @throws InvalidValueException
	 */
	public function setValue($value)
	{
		if ($value !== null && ! in_array($value = Helpers::sanitizeValue($value), $this->getValues(), true)) {
			throw new InvalidValueException(sprintf(
				"Value %s%s does not match optional values [ %s ].",
				Helpers::export($value),
				$this->getProperty() !== null ? sprintf(" for property '%s'", $this->getProperty()->getName()) : '',
				Helpers::export($this->getValues())
			));
		}

		return $value;
	}


	/**
	 * @return bool
	 */
	public function preserveKeys(): bool
	{
		return false;
	}


	/**
	 * @return bool
	 */
	public function multipleValues(): bool
	{
		return false;
	}


	/**
	 * @return array
	 */
	public function getValues(): array
	{
		return $this->values;
	}


	/**
	 * @return mixed|null
	 */
	public function getValue()
	{
		return $this->property->getValue();
	}


	/**
	 * @param  Property $property
	 * @return StaticArrayProvider
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
