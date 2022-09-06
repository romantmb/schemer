<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;

use Schemer\Support\Helpers;


/**
 * Nullable numeric (integer or float) user input validator
 */
class NullableNumericInput extends BasicInput
{

	public function __construct(mixed $value, ?string $name = null)
	{
		parent::__construct(Helpers::sanitizeValue($value), $name);
	}


	public function isValid(): bool
	{
		return is_int($this->value) || is_float($this->value) || $this->isEmpty();
	}


	public function isNullable(): bool
	{
		return true;
	}


	public function isEmpty(): bool
	{
		return $this->isNull() || $this->value === 0 || $this->value === 0.0;
	}


	public function getIssue(): ?string
	{
		return ! $this->isValid()
			? sprintf('must be numeric, %s given', gettype($this->value))
			: null;
	}
}
