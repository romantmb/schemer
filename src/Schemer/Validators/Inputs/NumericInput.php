<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;


/**
 * Numeric (integer or float) user input validator
 *
 * @author Roman Pistek
 */
final class NumericInput extends NullableNumericInput
{

	/**
	 * @return bool
	 */
	function isValid(): bool
	{
		return !$this->isEmpty() && parent::isValid();
	}


	/**
	 * @return bool
	 */
	function isNullable(): bool
	{
		return false;
	}


	/**
	 * @return string|null
	 */
	function getIssue(): ?string
	{
		if ($this->isUndefined()) {
			return 'must be defined';
		}
		if ($this->isEmpty()) {
			return 'must not be zero';
		}
		return parent::getIssue();
	}
}
