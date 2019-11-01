<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;


/**
 * Textual user input validator
 *
 * @author Roman Pistek
 */
class TextualInput extends NullableTextualInput
{

	/**
	 * @return bool
	 */
	function isValid()
	{
		return !$this->isEmpty() && parent::isValid();
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
			return 'must not be empty';
		}
		return parent::getIssue();
	}
}
