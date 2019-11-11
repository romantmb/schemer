<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Extensions;

use Schemer\Property;
use Schemer\Options;
use Schemer\Validators\Inputs\NullableBooleanInput;
use Schemer\ValueProvider;
use Schemer\ManyValuesProvider;
use Schemer\UserValueProvider;
use Schemer\Extensions\Transformers\SchemePathToInputNameTransformer;
use Schemer\Exceptions\SchemerException;
use Schemer\Exceptions\InvalidNodeException;
use Schemer\Exceptions\InvalidValueException;


class FormInputSpecification
{
	/** @var FormsForSchemer */
	private $service;

	/** @var Property */
	private $property;

	/** @var string */
	private $type;

	/** @var bool */
	private $required;

	/** @var bool */
	private $disabled = false;

	/** @var array */
	private $options;

	/** @var Property */
	private $propertyWithUniqueKey;

	/** @var object */
	private $formControl;

	/** @var bool */
	private $valueProviderRefreshed = false;


	/**
	 * @param Property        $property
	 * @param FormsForSchemer $service
	 * @throws InvalidNodeException
	 */
	public function __construct(Property $property, FormsForSchemer $service)
	{
		$this->property = $property;

		$this->service = $service;

		if (is_array($property->getValueProvider()) || !empty($property->getOptionalValues())) {
			$this->type = 'select';

		} elseif (($userValueProvider = $property->getValueProvider())  && $userValueProvider instanceof UserValueProvider) {

			if (is_a($userValueProvider->getValidatorClass(), NullableBooleanInput::class, true)) {
				$this->type = 'switch';

			} else {
				$this->type = 'text';
			}

		} else {
			$valueProvider = $property->getValueProvider();
			$invalid = is_object($valueProvider) ? sprintf('instance of %s', get_class($valueProvider)) : gettype($valueProvider);
			throw new InvalidNodeException(sprintf('Array with options or an UserValueProvider expected, %s given.', $invalid));
		}
	}


	/**
	 * @return bool
	 */
	public function isSelect(): bool
	{
		return $this->type === 'select';
	}


	/**
	 * @return bool
	 */
	public function isSwitch(): bool
	{
		return $this->type === 'switch';
	}


	/**
	 * @return bool
	 */
	public function isRequired(): bool
	{
		if ($this->required !== null) {
			return $this->required;
		}

		if ($this->isSwitch()) {
			return false; // switch must be set explicitly via setAsRequired()
		}

		if ($this->isSelect()) {
			return $this->required = $this->property->isUniqueKey() || $this->property->hasAnyConditionalSiblings();
		}

		if ($this->property->getValueProvider() instanceof ValueProvider) {
			try {
				(clone $this->property->getValueProvider())->setValue(null);
				return $this->required = false;

			} catch (InvalidValueException $e) {
				return $this->required = true;
			}
		}

		return $this->required = false;
	}


	/**
	 * @return bool
	 */
	public function isDisabled(): bool
	{
		return $this->disabled;
	}


	/**
	 * @return static
	 */
	public function setAsRequired(): FormInputSpecification
	{
		$this->required = true;

		return $this;
	}


	/**
	 * @return FormInputSpecification
	 */
	public function setAsDisabled(): FormInputSpecification
	{
		$this->disabled = true;

		return $this;
	}


	/**
	 * @param object|null $control
	 */
	public function setFormControl(object $control = null)
	{
		$this->formControl = $control;
	}


	/**
	 * @return object
	 */
	public function getFormControl()
	{
		return $this->formControl;
	}


	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->property->getPath();
	}


	/**
	 * @return string
	 */
	public function getInputName(): string
	{
		return SchemePathToInputNameTransformer::transform($this->getPath());
	}


	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->property->getName();
	}


	/**
	 * @return string|null
	 */
	public function getGroup(): ?string
	{
		return $this->property->getAnyKey();
	}


	/**
	 * @return string|null
	 */
	public function getGroupHash(): ?string
	{
		return ($group = $this->getGroup()) ? substr(md5($group), 0, 10) : null;
	}


	/**
	 * @return string
	 * @throws SchemerException
	 */
	public function getLabel(): string
	{
		return $this->service->makeSlugHumanReadable($this->getName());
	}


	/**
	 * @return array
	 */
	public function getOptions(): array
	{
		if ($this->options === null) {
			$this->options = $this->initializeOptions();
		}

		return $this->options;
	}


	/**
	 * @return mixed|null
	 */
	public function getValue()
	{
		$this->refreshPropertyInValueProvider();

		$value = $this->property->getValue();

		if ($this->isSelect() && $this->property->isInOptions()) {
			return $value ? sprintf('%s=%s', $this->getName(), $value) : null;
		}

		return $value;
	}


	/**
	 * @return string
	 * @throws SchemerException
	 */
	public function getHumanValue(): string
	{
		$this->refreshPropertyInValueProvider();

		if ($this->property->getValueProvider() instanceof UserValueProvider
			&& ($value = $this->property->getValueProvider()->getHumanValue()) !== null) {
			return $value;
		}

		if (is_array($value = $this->property->getValue())) {
			$value = implode(',', $value);
		}

		return $this->service->makeSlugHumanReadable(sprintf('%s:%s', $this->getName(), $value));
	}


	/**
	 * @return string|null
	 */
	public function getValidatorClass(): ?string
	{
		return ($provider = $this->property->getValueProvider()) instanceof UserValueProvider
			? $provider->getValidatorClass()
			: null;
	}


	/**
	 * @return bool
	 */
	public function hasUniqueKey(): bool
	{
		return $this->property->isUniqueKey();
	}


	/**
	 * @param Property|null $property
	 * @return FormInputSpecification
	 */
	public function setPropertyWithUniqueKey(Property $property = null): FormInputSpecification
	{
		if ($property !== null && $property->isUniqueKey() && $property !== $this->property) {
			$this->propertyWithUniqueKey = $property;
		}

		return $this;
	}


	/**
	 * @return Property
	 */
	public function getProperty(): Property
	{
		return $this->property;
	}


	/**
	 * @return array
	 * @throws SchemerException
	 */
	public function export(): array
	{
		return [
			'type'           => $this->type,
			'isRequired'     => $this->isRequired(),
			'isDisabled'     => $this->isDisabled(),
			'path'           => $this->getPath(),
			'name'           => $this->getName(),
			'inputName'      => $this->getInputName(),
			'group'          => $this->getGroup(),
			'groupHash'      => $this->getGroupHash(),
			'label'          => $this->getLabel(),
			'options'        => $this->getOptions(),
			'value'          => $this->getValue(),
			'humanValue'     => $this->getHumanValue(),
			'validatorClass' => $this->getValidatorClass(),
			'hasUniqueKey'   => $this->hasUniqueKey(),
		];
	}


	/**
	 * @return array
	 */
	private function initializeOptions(): array
	{
		if ($this->isSelect()) {

			$valueProvider = $this->property->getValueProvider();
			$preserveKeys = $valueProvider instanceof ManyValuesProvider ? $valueProvider->preserveKeys() : false;
			$options = is_array($valueProvider) ? $valueProvider : $this->property->getOptionalValues();

			return collect($options)
				->mapWithKeys(function($value, $key) use ($preserveKeys) {

					$key = $this->property->isInOptions()
						? sprintf('%s=%s', $this->getName(), $preserveKeys ? $key : $value)
						: ($preserveKeys ? $key : $value);

					$title = $this->service->makeSlugHumanReadable(sprintf('%s:%s', $this->getName(), $value));

					return [ $key => $title ];
				})
				->all();
		}

		if ($this->property instanceof Options) {
			return $this->property->getItems();
		}

		return [];
	}


	private function refreshPropertyInValueProvider(): void
	{
		if ($this->valueProviderRefreshed === true) {
			return;
		}

		if ($this->property->getValueProvider() instanceof ValueProvider) {
			$this->property->getValueProvider()
				->setProperty($this->getProperty());
		}

		$this->valueProviderRefreshed = true;
	}
}
