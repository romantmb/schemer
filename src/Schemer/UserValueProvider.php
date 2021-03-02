<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;

use Schemer\Validators\Input;
use Schemer\Validators\UserInputValidator;
use Schemer\Exceptions\InvalidUserInputException;
use Schemer\Exceptions\InvalidValueException;
use InvalidArgumentException;
use Exception;


class UserValueProvider implements ValueProvider
{
	/** @var string */
	private $validatorClass;

	/** @var Property */
	private $property;


	/**
	 * @param string $validatorClass
	 */
	public function __construct(string $validatorClass)
	{
		if (! is_subclass_of($validatorClass, Input::class)) {
			throw new InvalidArgumentException(sprintf('Validator must be an instance of %s, %s given.', Input::class, $validatorClass));
		}

		$this->validatorClass = $validatorClass;
	}


	/**
	 * @return string
	 */
	public function getValidatorClass(): string
	{
		return $this->validatorClass;
	}


	/**
	 * @param $value
	 * @return mixed
	 * @throws Exception
	 */
	public function setValue($value)
	{
		try {
			return UserInputValidator::simple(
				new $this->validatorClass(
					$value,
					$this->getProperty() !== null ? sprintf("'%s'", $this->getProperty()->getPath()) : null
				)
			);

		} catch (InvalidUserInputException $e) {
			throw new InvalidValueException($e->getMessage(), 0, $e);
		}
	}


	/**
	 * @return mixed|null
	 */
	public function getValue()
	{
		return $this->property->getRawValue();
	}


	/**
	 * To be overloaded if necessary
	 *
	 * @return mixed|null
	 */
	public function getHumanValue()
	{
		return null;
	}


	/**
	 * @param  Property $property
	 * @return UserValueProvider
	 */
	public function setProperty(Property $property): ValueProvider
	{
		$this->property = $property;

		return $this;
	}


	/**
	 * @return Property|null
	 */
	public function getProperty(): ?Property
	{
		return $this->property;
	}
}
