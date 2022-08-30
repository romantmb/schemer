<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;

use Schemer\Support\Strings;
use InvalidArgumentException;


/**
 * User input validator with custom validation
 *
 * @author Roman Pistek
 */
class CustomInput extends BasicInput
{
	/** @var callable */
	protected $validator;


	/**
	 * @param  mixed  $value
	 * @param  string $name
	 * @throws InvalidArgumentException
	 */
	public function __construct($value, $name = null)
	{
		parent::__construct($value, $name);

		if (is_string($this->value)) {
			$this->value = Strings::trim($this->value);
		}
	}


	/**
	 * @param callable $validator
	 * @return static
	 */
	public function validate(callable $validator)
	{
		$this->validator = $validator;

		return $this;
	}


	/**
	 * @return bool
	 */
	protected function validator()
	{
		// to be overloaded
		return true;
	}


	/**
	 * @return bool
	 */
	function isValid(): bool
	{
		return is_callable($cbValidator = $this->validator)
			? $cbValidator($this->value) : $this->validator();
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
		return $this->isNull() || $this->value === '';
	}


	/**
	 * @param  bool $unmodified if true, original input value is returned
	 * @return mixed
	 */
	function getValue($unmodified = false)
	{
		return parent::getValue($unmodified) ?: null;
	}


	/**
	 * @return string|null
	 */
	function getIssue(): ?string
	{
		if (! $this->isValid()) {
			return sprintf("value ('%s') is not valid", $this->value);
		}
		return null;
	}
}
