<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;


/**
 * Nullable numeric (integer or float) user input validator
 *
 * @author Roman Pistek
 */
class NullableNumericInput extends BasicInput
{

	/**
	 * @param mixed       $value
	 * @param string|null $name
	 */
	public function __construct($value, $name = null)
	{
		if (is_string($value) && is_numeric($value)) {
			$value = strpos($value, '.') !== false ? (float) $value : (int) $value;
		}

		parent::__construct($value, $name);
	}


	/**
	 * @return bool
	 */
	function isValid()
	{
		return is_int($this->value) || is_float($this->value) || $this->isEmpty();
	}


	/**
	 * @return bool
	 */
	function isNullable()
	{
		return true;
	}


	/**
	 * @return bool
	 */
	function isEmpty()
	{
		return $this->isNull() || $this->value === 0 || $this->value === 0.0;
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
	function getIssue()
	{
		if (!$this->isValid()) {
			return sprintf('must be numeric, %s given', gettype($this->value));
		}
		return null;
	}
}
