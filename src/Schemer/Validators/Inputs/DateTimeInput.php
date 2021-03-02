<?php /** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;


/**
 * Date/time input validator
 *
 * @author Roman Pistek
 */
final class DateTimeInput extends BasicInput
{

	/**
	 * @return bool
	 */
	function isValid(): bool
	{
		return ! $this->isEmpty() && parent::isValid();
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
			return 'must not be empty';
		}
		return parent::getIssue();
	}
}
