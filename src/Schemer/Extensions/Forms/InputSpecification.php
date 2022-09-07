<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Forms;

use InvalidArgumentException;
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


class InputSpecification
{
	private string $type;

	private bool $required;

	private bool $disabled = false;

	private bool $hidden = false;

	private array $options;

	private Property $propertyWithUniqueKey;

	private object $formControl;

	private bool $valueProviderRefreshed = false;


	public function __construct(private Property $property, private SchemeForm $form)
	{
		$this->setType(match (true) {
			is_array($userValueProvider = $property->getValueProvider()) || ! empty($property->getOptionalValues()) =>
				$userValueProvider instanceof ManyValuesProvider && $userValueProvider->multipleValues()
					? 'multiselect'
					: 'select',
			($userValueProvider = $property->getValueProvider()) && $userValueProvider instanceof UserValueProvider =>
				is_a($userValueProvider->getValidatorClass(), NullableBooleanInput::class, true)
					? 'switch'
					: 'text',
			default => throw new InvalidNodeException(sprintf(
				'Array with options or an UserValueProvider expected, %s given.', $property->getValueProvider()::class))
		});
	}


	public function setType(string $type): self
	{
		$this->type = match($type) {
			'select', 'multiselect', 'switch', 'text', 'longtext' => $type,
			default => throw new InvalidArgumentException(sprintf("'%s' is not a valid type.", $type)),
		};
		return $this;
	}


	public function isSelect(): bool
	{
		return $this->type === 'select';
	}


	public function isMultiSelect(): bool
	{
		return $this->type === 'multiselect';
	}


	public function isSwitch(): bool
	{
		return $this->type === 'switch';
	}


	public function isRequired(): bool
	{
		if (isset($this->required)) {
			return $this->required;
		}

		if ($this->isSwitch()) {
			return false; // switch must be set explicitly via setAsRequired()
		}

		if ($this->isSelect()) {
			return $this->required = ($this->property->isUniqueKey() || $this->property->hasAnyConditionalSiblings());
		}

		if ($this->property->getValueProvider() instanceof ValueProvider) {
			try {
				(clone $this->property->getValueProvider())->setValue(null);
				return $this->required = false;

			} catch (InvalidValueException) {
				return $this->required = true;
			}
		}

		return $this->required = false;
	}


	public function isDisabled(): bool
	{
		return $this->disabled;
	}


	public function isHidden(): bool
	{
		return $this->hidden;
	}


	public function setAsRequired(): InputSpecification
	{
		$this->required = true;
		return $this;
	}


	public function setAsDisabled(): InputSpecification
	{
		$this->disabled = true;
		return $this;
	}


	public function setAsHidden(): InputSpecification
	{
		$this->hidden = true;
		return $this;
	}


	public function setFormControl(object $control = null): void
	{
		$this->formControl = $control;
	}


	public function getFormControl(): object
	{
		return $this->formControl;
	}


	public function getPath(): string
	{
		return $this->property->getPath();
	}


	public function getInputName(): string
	{
		return SchemePathToInputNameTransformer::transform($this->getPath());
	}


	public function getName(): string
	{
		return $this->property->getName();
	}


	public function getGroup(): ?string
	{
		return $this->property->getAnyKey();
	}


	public function getGroupHash(): ?string
	{
		return ($group = $this->getGroup()) ? substr(md5($group), 0, 10) : null;
	}


	/**
	 * @throws SchemerException
	 */
	public function getLabel(): string
	{
		return $this->form->humanReadableSlug($this->getName());
	}


	public function getOptions(): array
	{
		return $this->options ??= $this->initializeOptions();
	}


	public function getValue()
	{
		$this->refreshPropertyInValueProvider();

		$value = $this->property->getValue();

		if ($this->property->isInOptions() && ($this->isSelect() || $this->isMultiSelect())) {

			if (is_array($value)) {
				return collect($value)
					->map(function($v) {
						return sprintf('%s=%s', $this->getName(), $v);
					})
					->all();
			}

			return $value ? sprintf('%s=%s', $this->getName(), $value) : null;
		}

		return $value;
	}


	/**
	 * @throws SchemerException
	 */
	public function getHumanValue(): string
	{
		$this->refreshPropertyInValueProvider();

		if (($provider = $this->property->getValueProvider()) && $provider instanceof UserValueProvider
			&& ($value = $provider->getHumanValue()) !== null) {
			return $value;
		}

		if (is_array($value = $this->property->getValue())) {
			$value = implode(',', $value);
		}

		return $this->form->humanReadableSlug(sprintf('%s:%s', $this->getName(), $value));
	}


	public function getValidatorClass(): ?string
	{
		return ($provider = $this->property->getValueProvider()) && $provider instanceof UserValueProvider
			? $provider->getValidatorClass()
			: null;
	}


	public function hasUniqueKey(): bool
	{
		return $this->property->isUniqueKey();
	}


	public function hasAnyConditionalSiblings(): bool
	{
		return $this->property->hasAnyConditionalSiblings();
	}


	public function setPropertyWithUniqueKey(?Property $property = null): InputSpecification
	{
		if ($property !== null && $property !== $this->property && $property->isUniqueKey()) {
			$this->propertyWithUniqueKey = $property;
		}

		return $this;
	}


	public function getPropertyWithUniqueKey(): ?Property
	{
		return $this->propertyWithUniqueKey;
	}


	public function getProperty(): Property
	{
		return $this->property;
	}


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


	private function initializeOptions(): array
	{
		if ($this->isSelect() || $this->isMultiSelect()) {

			$valueProvider = $this->property->getValueProvider();
			$preserveKeys = $valueProvider instanceof ManyValuesProvider ? $valueProvider->preserveKeys() : false;

			return collect($this->property->getOptionalValues())
				->mapWithKeys(function($value, $key) use ($preserveKeys) {

					$v = $preserveKeys ? $key : $value;

					$key = $this->property->isInOptions()
						? sprintf('%s=%s', $this->getName(), $v)
						: $v;

					$title = $this->form->humanReadableSlug(sprintf('%s:%s', $this->getName(), $value));

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
