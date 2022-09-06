<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;


/**
 * Nullable boolean user input validator
 */
class NullableBooleanInput extends BasicInput
{

	public function isValid(): bool
	{
		return is_bool($this->getValue()) || $this->isEmpty();
	}


	public function isNullable(): bool
	{
		return true;
	}


	public function isEmpty(): bool
	{
		return $this->isNull();
	}


	public function getIssue(): ?string
	{
		return ! $this->isValid()
			? sprintf('must be boolean, %s given', gettype($this->getValue()))
			: null;
	}
}
