<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;

use Schemer\Support\Helpers;
use Schemer\Exceptions\InvalidValueException;
use Nette\Utils\Arrays;


class StaticArrayProvider implements ManyValuesProvider
{
	private ?Property $property = null;


	public function __construct(private array $values)
	{
		if (Arrays::isList($values)) {
			$this->values = array_combine($values, $values);
		}
	}


	public function setValue(mixed $value): mixed
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


	public function preserveKeys(): bool
	{
		return false;
	}


	public function multipleValues(): bool
	{
		return false;
	}


	public function getValues(): array
	{
		return $this->values;
	}


	public function getValue(): mixed
	{
		return $this->property->getValue();
	}


	public function setProperty(Property $property): StaticArrayProvider
	{
		$this->property = $property;
		return $this;
	}


	public function getProperty(): ?Property
	{
		return $this->property;
	}
}
