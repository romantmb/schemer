<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;

use Schemer\Exceptions\InvalidValueException;


class ArrayItem
{

	public function __construct(private mixed $value, private ?string $key = null)
	{
		if ($value !== null && ! is_scalar($value)) {
			throw new InvalidValueException(sprintf('Array value must be of scalar type, %s given.', gettype($value)));
		}

		$this->value = $value ?: null;
		$this->key = ! is_string($key) || ! $key ? (string) $this->value : $key;

		if (! $this->value && ! $this->key) {
			throw new InvalidValueException('Non-associative array item with empty value is not allowed.');
		}
	}


	public function getValue(): mixed
	{
		return $this->value;
	}


	public function getKey(): ?string
	{
		return $this->key;
	}
}
