<?php /** @noinspection PhpUnused */

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;

use Schemer\Exceptions\InvalidValueException;
use Schemer\Support\Helpers;
use Schemer\Exceptions\InvalidNodeException;
use Schemer\Exceptions\UndeterminedPropertyException;
use BadMethodCallException;


final class Property extends Node implements NamedNodeWithValue
{
	/** @var string */
	private $name;

	/** @var ManyValuesProvider */
	private $optionalValuesProvider;

	/** @var ValueProvider */
	private $valueProvider;

	/** @var mixed|null */
	private $value;

	/** @var mixed */
	private $defaultValue;

	/** @var Node[] */
	private $conditionalSiblings = [];

	/** @var bool */
	private $isUniqueKey = false;


	/**
	 * @param string    $name
	 * @param Node|null $parent
	 * @throws InvalidNodeException
	 */
	public function __construct(string $name, Node $parent = null)
	{
		parent::__construct($parent);

		if (! $name) {
			throw new InvalidNodeException('Property name must be defined.');
		}

		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * @return bool
	 */
	public function isBag(): bool
	{
		return ! empty($this->children);
	}


	/**
	 * @return static
	 */
	public function uniqueKey(): Property
	{
		$this->isUniqueKey = true;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isUniqueKey(): bool
	{
		return $this->isUniqueKey;
	}


	/**
	 * @param  ManyValuesProvider $provider
	 * @return self
	 */
	public function setOptionalValues(ManyValuesProvider $provider): self
	{
		$this->optionalValuesProvider = $provider->setProperty($this);

		return $this;
	}


	/**
	 * @return array
	 */
	public function getOptionalValues(): array
	{
		return $this->optionalValuesProvider ? $this->optionalValuesProvider->getValues() : [];
	}


	/**
	 * @param  mixed|null $value
	 * @return Property
	 */
	public function setValue($value): self
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


	/**
	 * @param mixed $value
	 * @return static
	 */
	public function setDefaultValue($value): self
	{
		$this->defaultValue = $value;

		return $this;
	}


	/**
	 * Shortcut of setDefaultValue()
	 *
	 * @param mixed $value
	 * @return static
	 */
	public function default($value): self
	{
		return $this->setDefaultValue($value);
	}


	/**
	 * @return mixed|null
	 */
	public function getValue()
	{
		if ($this->isBag()) {
			return null;
		}

		if (($value = $this->value) === null && $this->valueProvider instanceof ValueProvider) {
			$value = $this->valueProvider->getValue();
		}

		return $value ?? $this->defaultValue;
	}


	/**
	 * @return ValueProvider|null
	 */
	public function getValueProvider(): ?ValueProvider
	{
		return $this->optionalValuesProvider ?: $this->valueProvider;
	}


	/**
	 * Defines conditional siblings for specific property value
	 *
	 * @param string $value
	 * @param Node   $node
	 * @return Property
	 * @throws InvalidNodeException
	 */
	public function on($value, Node $node): Property
	{
		$key = self::getConditionalKey($value);

		if (array_key_exists($key, $this->conditionalSiblings)) {
			throw new InvalidNodeException(sprintf("Conditional %s for value %s already defined.", $node instanceof Group ? 'sibling' : 'siblings', Helpers::export($value)));
		}

		$this->conditionalSiblings[$key] = $node;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasAnyConditionalSiblings(): bool
	{
		return ! empty($this->conditionalSiblings);
	}


	/**
	 * @return Node[]
	 * @throws UndeterminedPropertyException
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


	/**
	 * @param string $name
	 * @return Property|null
	 * @throws UndeterminedPropertyException
	 */
	public function getSibling(string $name): ?Property
	{
		foreach ($this->getSiblings() as $sibling) {
			if ($sibling instanceof static && $sibling->getName() === $name) {
				return $sibling;
			}
		}

		return null;
	}


	/**
	 * @return Options|null
	 */
	public function isInOptions(): ?Options
	{
		return ($wrapper = $this->getParent())
			&& ($options = $wrapper->getParent()) && $options instanceof Options
			? $options : null;
	}


	/**
	 * @return string
	 */
	public function getPath(): string
	{
		$path = parent::getPath();

		if (($options = $this->isInOptions())
			&& ($uniqueKeyProperty = $options->getUniqueKeyProperty())
			&& ($parent = $this->getParent()) && $parent->getKey() === null) {

			/**
			 * 'options.someProperty' -> 'options[uniqueProp=*].someProperty'
			 */
			$path = substr_replace(
				$path,
				! $this->isUniqueKey() ? sprintf('[%s=*]', $uniqueKeyProperty->getName()) : '',
				strpos($path, '.' . $this->getName()),
				$this->isUniqueKey() ? (strlen($this->getName()) + 1) : 0
			);
		}

		return $path;
	}


	/**
	 * @return Node[]
	 */
	public function getUndeterminedSiblings(): array
	{
		return $this->conditionalSiblings;
	}


	/**
	 * @return array|mixed|null
	 */
	public function toArray()
	{
		if (! $this->isBag()) {
			return $this->getValue();
		}

		return parent::toArray();
	}


	/**
	 * @param  int $options
	 * @return mixed|string|null
	 */
	public function toJson($options = 0)
	{
		if (! $this->isBag()) {
			return $this->getValue();
		}

		return parent::toJson($options);
	}


	/**
	 * @internal
	 * @return mixed|null
	 */
	public function getRawValue()
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


	/**
	 * @param mixed $value
	 * @return string
	 */
	private static function getConditionalKey($value): string
	{
		if (! is_scalar($value)) {
			throw new InvalidValueException(sprintf('Conditional value must be scalar, %s given.'. gettype($value)));
		}

		return trim(Helpers::export($value), '\'"');
	}
}
