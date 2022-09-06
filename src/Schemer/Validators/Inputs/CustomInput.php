<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;

use Schemer\Support\Strings;
use Closure;


/**
 * User input validator with custom validation
 */
class CustomInput extends BasicInput
{
	protected ?Closure $_validator = null;


	public function __construct(mixed $value, ?string $name = null)
	{
		parent::__construct(is_string($value) ? Strings::trim($value) : $value, $name);
	}


	public function validate(callable $validator): static
	{
		$this->_validator = Closure::fromCallable($validator);
		return $this;
	}


	protected function validator(): bool
	{
		// to be overloaded
		return true;
	}


	public function isValid(): bool
	{
		return isset($this->_validator) ? ($this->_validator)($this->value) : $this->validator();
	}


	public function isNullable(): bool
	{
		return true;
	}


	public function isEmpty(): bool
	{
		return $this->isNull() || $this->value === '';
	}


	public function getValue(bool $unmodified = false): mixed
	{
		return parent::getValue($unmodified) ?: null;
	}


	public function getIssue(): ?string
	{
		return ! $this->isValid()
			? sprintf("value ('%s') is not valid", $this->value)
			: null;
	}
}
