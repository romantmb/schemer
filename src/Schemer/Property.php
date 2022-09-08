<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;

use Schemer\Exceptions\InvalidValueException;
use Schemer\Support\Helpers;
use Schemer\Exceptions\InvalidNodeException;
use Schemer\Exceptions\UndeterminedPropertyException;
use BadMethodCallException;


final class Property extends Node implements NamedNodeWithValue
{
	private ?ManyValuesProvider $optionalValuesProvider = null;

	private ?ValueProvider $valueProvider = null;

	private mixed $value = null;

	private mixed $defaultValue = null;

	private array $conditionalSiblings = [];

	private bool $isUniqueKey = false;


	public function __construct(private string $name, ?Node $parent = null)
	{
		if (! $name) {
			throw new InvalidNodeException('Property name must be defined.');
		}

		parent::__construct($parent);
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function isBag(): bool
	{
		return ! empty($this->children);
	}


	public function uniqueKey(): Property
	{
		$this->isUniqueKey = true;
		return $this;
	}


	public function isUniqueKey(): bool
	{
		return $this->isUniqueKey;
	}


	public function setOptionalValues(ManyValuesProvider $provider): self
	{
		$this->optionalValuesProvider = $provider->setProperty($this);
		return $this;
	}


	public function getOptionalValues(): array
	{
		return $this->optionalValuesProvider?->getValues() ?? [];
	}


	public function setValue(mixed $value): self
	{
		if ($this->isBag()) {
			throw new BadMethodCallException('Cannot set value of bag property.');
		}

		if ($this->optionalValuesProvider instanceof ManyValuesProvider) {
			$this->value = $this->optionalValuesProvider->setValue($value);

		} elseif ($value instanceof ValueProvider) {
			$this->valueProvider = $value->setProperty($this);

		} elseif ($value === null || is_scalar($value) || is_array($value)) {
			if ($this->valueProvider instanceof ValueProvider) {
				$this->value = $this->valueProvider->setValue($value);

			} else {
				$this->value = $value;
			}
		}

		return $this;
	}


	public function setDefaultValue(mixed $value): self
	{
		$this->defaultValue = $value;
		return $this;
	}


	/**
	 * Shortcut of setDefaultValue()
	 */
	public function default(mixed $value): self
	{
		return $this->setDefaultValue($value);
	}


	public function getValue(): mixed
	{
		if ($this->isBag()) {
			return null;
		}

		if (($value = $this->value) === null && $this->valueProvider instanceof ValueProvider) {
			$value = $this->valueProvider->getValue();
		}

		return $value ?? $this->defaultValue;
	}


	public function getValueProvider(): ?ValueProvider
	{
		return $this->optionalValuesProvider ?? $this->valueProvider;
	}


	/**
	 * Defines conditional siblings for specific property value
	 */
	public function on(mixed $value, Node $node): self
	{
		if (array_key_exists($key = self::getConditionalKey($value), $this->conditionalSiblings)) {
			throw new InvalidNodeException(sprintf(
				"Conditional %s for value %s already defined.",
				$node instanceof Group ? 'sibling' : 'siblings', Helpers::export($value)));
		}

		$this->conditionalSiblings[$key] = $node;
		return $this;
	}


	public function hasAnyConditionalSiblings(): bool
	{
		return ! empty($this->conditionalSiblings);
	}


	/**
	 * @return array<Node>
	 */
	public function getSiblings(): array
	{
		if (! $this->hasAnyConditionalSiblings()) {
			return [];
		}

		if (($value = $this->getValue()) === null) {
			throw new UndeterminedPropertyException(sprintf("Cannot get conditional siblings, optional value of property '%s' is not specified.", $this->getName()));
		}

		$siblings = @$this->conditionalSiblings[self::getConditionalKey($value)] ?: [];

		if (($group = $siblings) instanceof Group) {
			$siblings = collect($group)->flatten()->all();

		} elseif ($siblings instanceof Node) {
			$siblings = [ $siblings ];
		}

		foreach ($siblings as $sibling) {
			/** @var Property $member */
			$sibling
				->setParent($this->getParent())
				->updateParentalLinks();
		}

		return $siblings;
	}


	public function getSibling(string $name): ?Property
	{
		foreach ($this->getSiblings() as $sibling) {
			if ($sibling instanceof self && $sibling->getName() === $name) {
				return $sibling;
			}
		}
		return null;
	}


	public function isInOptions(): ?Options
	{
		return ($wrapper = $this->getParent())
			&& ($options = $wrapper->getParent()) && $options instanceof Options
			? $options : null;
	}


	public function getPath(): string
	{
		$path = parent::getPath();

		if (($options = $this->isInOptions())
			&& ($uniqueKeyProperty = $options->getUniqueKeyProperty())
			&& ($parent = $this->getParent()) && $parent->getKey() === null) {

			/**
			 * 'options.someProperty' -> 'options[uniqueProp=*].someProperty'
			 */

			$wildcard = //$this->isUniqueKey() ? '' :
				sprintf('[%s=*]', $uniqueKeyProperty->getName());

			$path = str_replace($op = $options->getPath(), "$op$wildcard", $path);

//			$path = substr_replace(
//				$path,
//				! $this->isUniqueKey() ? sprintf('[%s=*]', $uniqueKeyProperty->getName()) : '',
//				strpos($path, '.' . $this->getName()),
//				$this->isUniqueKey() ? (strlen($this->getName()) + 1) : 0
//			);
		}

		return $path;
	}


	/**
	 * @return array<Node>
	 */
	public function getUndeterminedSiblings(): array
	{
		return $this->conditionalSiblings;
	}


	public function toJson($options = 0): string
	{
		return ! $this->isBag() ? $this->getValue() : parent::toJson($options);
	}


	/**
	 * @internal
	 */
	public function getRawValue(): mixed
	{
		return $this->value;
	}


	public function __clone()
	{
		parent::__clone();

		foreach ($this->conditionalSiblings as & $sibling) {
			$sibling = clone $sibling;
		}
		unset($sibling);

		if ($this->valueProvider !== null) {
			$this->valueProvider = clone $this->valueProvider;
		}

		if ($this->optionalValuesProvider !== null) {
			$this->optionalValuesProvider = clone $this->optionalValuesProvider;
		}
	}


	private static function getConditionalKey(mixed $value): string
	{
		return is_scalar($value)
			? trim(Helpers::export($value), '\'"')
			: throw new InvalidValueException(sprintf('Conditional value must be scalar, %s given.', gettype($value)));
	}
}
