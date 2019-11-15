<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;


/**
 * Nullable boolean user input validator
 *
 * @author Roman Pistek
 */
class NullableBooleanInput extends BasicInput
{

	/**
	 * @return bool
	 */
	function isValid(): bool
	{
		return is_bool($this->getValue()) || $this->isEmpty();
	}


	/**
	 * @return bool
	 */
	function isNullable(): bool
	{
		return true;
	}


	/**
	 * @return bool
	 */
	function isEmpty(): bool
	{
		return $this->isNull();
	}


	/**
	 * @param  bool $unmodified if true, original input value is returned
	 * @return mixed
	 */
	function getValue($unmodified = false)
	{
		return parent::getValue($unmodified);
	}


	/**
	 * @return string|null
	 */
	function getIssue(): ?string
	{
		if (!$this->isValid()) {
			return sprintf('must be boolean, %s given', gettype($this->getValue()));
		}
		return null;
	}
}
