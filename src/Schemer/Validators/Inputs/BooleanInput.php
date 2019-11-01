<?php

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
class BooleanInput extends NullableBooleanInput
{

	/**
	 * @return bool
	 */
	function isValid()
	{
		return is_bool($this->getValue());
	}


	/**
	 * @return bool
	 */
	function isNullable()
	{
		return false;
	}


	/**
	 * @return string|null
	 */
	function getIssue()
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
