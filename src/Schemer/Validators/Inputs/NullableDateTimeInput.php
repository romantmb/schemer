<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;

use Nette\Utils\DateTime;
use Exception;


/**
 * Nullable date/time input validator
 *
 * @author Roman Pistek
 */
class NullableDateTimeInput extends BasicInput
{

	/**
	 * @return bool
	 */
	function isValid(): bool
	{
		try {
			return DateTime::from($this->value) || $this->isEmpty();

		} catch (Exception $e) {
			return false;
		}
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
		return $this->isNull() || ! $this->value;
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
		if (! $this->isValid()) {
			if (is_int($this->value)) {
				$mismatch = $this->value;
			} elseif (is_string($this->value)) {
				$mismatch = sprintf("'%s'", $this->value);
			} else {
				$mismatch = is_object($this->value) ? sprintf('instance of %s', get_class($this->value)) : gettype($this->value);
			}
			return sprintf('must represent date and/or time, %s given', $mismatch);
		}
		return null;
	}
}
