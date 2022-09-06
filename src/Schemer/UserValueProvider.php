<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;

use Schemer\Validators\Input;
use Schemer\Validators\UserInputValidator;
use Schemer\Exceptions\InvalidUserInputException;
use Schemer\Exceptions\InvalidValueException;
use InvalidArgumentException;
use Throwable;


class UserValueProvider implements ValueProvider
{
	protected ?Property $property = null;


	public function __construct(protected string $validatorClass)
	{
		if (! is_subclass_of($validatorClass, Input::class)) {
			throw new InvalidArgumentException(sprintf('Validator must be an instance of %s, %s given.', Input::class, $validatorClass));
		}
	}


	public function getValidatorClass(): string
	{
		return $this->validatorClass;
	}


	/**
	 * @throws Throwable
	 */
	public function setValue(mixed $value)
	{
		try {
			return UserInputValidator::simple(
				new $this->validatorClass(
					$value,
					$this->property !== null ? sprintf("'%s'", $this->property->getPath()) : null
				)
			);

		} catch (InvalidUserInputException $e) {
			throw new InvalidValueException($e->getMessage(), 0, $e);
		}
	}


	public function getValue()
	{
		return $this->property?->getRawValue();
	}


	/**
	 * To be overloaded if necessary
	 */
	public function getHumanValue(): mixed
	{
		return null;
	}


	public function setProperty(Property $property): UserValueProvider
	{
		$this->property = $property;
		return $this;
	}


	public function getProperty(): ?Property
	{
		return $this->property;
	}
}
