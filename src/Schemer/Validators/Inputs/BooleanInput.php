<?php /** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;


/**
 * Boolean user input validator
 *
 * @author Roman Pistek
 */
final class BooleanInput extends NullableBooleanInput
{

	/**
	 * @return bool
	 */
	function isValid(): bool
	{
		return is_bool($this->getValue());
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
			return 'must not be null';
		}
		return parent::getIssue();
	}
}
