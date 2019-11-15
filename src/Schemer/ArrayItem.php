<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;

use Schemer\Exceptions\InvalidValueException;


class ArrayItem
{
	/** @var string */
	private $key;

	/** @var mixed */
	private $value;


	/**
	 * @param mixed|null  $value
	 * @param string|null $key
	 */
	public function __construct($value, $key = null)
	{
		if ($value !== null && !is_scalar($value)) {
			throw new InvalidValueException(sprintf('Array value must be of scalar type, %s given.', gettype($value)));
		}

		$this->value = $value ?: null;

		$this->key = !is_string($key) || !$key ? (string) $this->value : $key;

		if (!$this->value && !$this->key) {
			throw new InvalidValueException('Non-associative array item with empty value is not allowed.');
		}
	}


	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}


	/**
	 * @return string|null
	 */
	public function getKey(): ?string
	{
		return $this->key;
	}
}
