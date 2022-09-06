<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;


/**
 * Numeric (integer or float) user input validator
 */
final class NumericInput extends NullableNumericInput
{

	public function isValid(): bool
	{
		return ! $this->isEmpty() && parent::isValid();
	}


	public function isNullable(): bool
	{
		return false;
	}


	public function getIssue(): ?string
	{
		return match (true) {
			$this->isUndefined() => 'must be defined',
			$this->isEmpty()     => 'must not be zero',
			default              => parent::getIssue(),
		};
	}
}
