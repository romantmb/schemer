<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;

use Schemer\Support\Strings;


/**
 * Nullable textual user input validator
 */
class NullableTextualInput extends BasicInput
{

	public function __construct(mixed $value, ?string $name = null)
	{
		parent::__construct(is_string($value) ? Strings::trim($value) : $value, $name);
	}


	public function isValid(): bool
	{
		return is_string($this->value) || $this->isEmpty();
	}


	public function isNullable(): bool
	{
		return true;
	}


	public function isEmpty(): bool
	{
		return $this->isNull() || $this->value === '';
	}


	public function getValue(bool $unmodified = false): ?string
	{
		return parent::getValue($unmodified) ?: null;
	}


	public function getIssue(): ?string
	{
		return ! $this->isValid()
			? sprintf('must be a string, %s given', gettype($this->value))
			: null;
	}
}
