<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;

use Schemer\Exceptions\InvalidNodeException;
use Schemer\Exceptions\InvalidValueException;
use Schemer\Exceptions\ItemNotFoundException;
use Schemer\Exceptions\InvalidUniqueKeyException;
use Schemer\Exceptions\UndeterminedPropertyException;
use Illuminate\Support\Collection;


final class Options extends Node implements NamedNode
{
	/** @var string */
	private $name;

	/** @var string */
	private $itemType;

	/** @var bool */
	private $associative;

	/** @var array */
	private $candidates = [];

	/** @var array */
	private $items = [];


	/**
	 * @param string    $name
	 * @param Node|null $parent
	 */
	public function __construct(string $name, Node $parent = null)
	{
		parent::__construct($parent);

		if (! $name) {
			throw new InvalidNodeException('Options name must be defined.');
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
	public function containsPrimitives(): bool
	{
		return ! in_array($this->itemType, [ null, 'node' ], true);
	}


	/**
	 * @param  mixed $items
	 * @return static
	 */
	public function setItems($items): Options
	{
		$this->reset();

		foreach (self::valuesFromProviderIfAny($items) as $key => $item) {
			$this->add($item, $key);
		}

		return $this;
	}


	/**
	 * @param  mixed $candidates
	 * @return static
	 */
	public function setCandidates($candidates): Options
	{
		$this->clear();

		foreach (self::valuesFromProviderIfAny($candidates) as $key => $candidate) {
			$this->addCandidate($candidate, $key);
		}

		return $this;
	}


	/**
	 * @param mixed      $child
	 * @param mixed|null $key
	 * @return Options
	 */
	public function add($child, $key = null): Node
	{
		return $this->addItem($this->items, $child, $key);
	}


	public function addCandidate($item, $key = null): Options
	{
		return $this->addItem($this->candidates, $item, $key);
	}


	public function getItems(): array
	{
		return $this->items;
	}


	public function getCandidates(bool $sortByPriority = true): Collection
	{
		if (empty($this->candidates)) {
			return collect();
		}

		if ($this->containsPrimitives()) {
			return collect([
				'key' => collect($this->candidates)
					->map(static function(ArrayItem $item) { return $item->getKey(); })
					->all()
			]);
		}

		$candidates = collect($this->candidates)
			->map(static function(Node $item) { return $item->getChildren(); })
			->flatten()
			->filter(static function(NamedNode $node) {
				return $node instanceof NamedNodeWithValue
					&& (! $node instanceof Property || ! $node->isBag());
			})
			->mapWithKeys(static function(NamedNodeWithValue $node) {
				return [
					$node->getName() => $node instanceof Property ? $node->getValueProvider() : $node->getValue()
				];
			});

		if ($sortByPriority === false) {
			return $candidates;
		}

		$sortedCandidates = collect();

		$candidates = $candidates
			->filter(static function(ValueProvider $provider = null, $key = null) use (& $sortedCandidates) {
				if ($provider !== null && ($prop = $provider->getProperty()) && $prop->isUniqueKey()) {
					$sortedCandidates->put($key ,$provider);
					return false;
				}
				return true;
			})
			->filter(static function(ValueProvider $provider = null, $key = null) use (& $sortedCandidates) {
				if ($provider !== null && ($prop = $provider->getProperty()) && $prop->hasAnyConditionalSiblings()) {
					$sortedCandidates->put($key ,$provider);
					return false;
				}
				return true;
			});

		return $sortedCandidates->merge($candidates);
	}


	/**
	 * @return Property|null
	 */
	public function getUniqueKeyProperty(): ?Property
	{
		return ! $this->containsPrimitives()
		&& ($candidate = $this->getCandidates()->first()) && $candidate instanceof ValueProvider
		&& ($property = $candidate->getProperty()) && $property instanceof Property
		&& $property->isUniqueKey()
			? $property : null;
	}


	/**
	 * @param bool $hard
	 */
	public function reset(bool $hard = false): void
	{
		$this->items = [];

		if (empty($this->candidates) || $hard === true) {
			$this->candidates = [];
			$this->itemType = $this->associative = null;
		}
	}


	public function beforeAdded(Node $parent): void
	{
		$this->validateDeclaration();
	}


	/**
	 * @param string      $definition
	 * @param string|null $value
	 * @param bool        $checkIfExistsOnly
	 * @return Node|ArrayItem|null
	 */
	public function pick(string $definition, $value = null, bool $checkIfExistsOnly = false)
	{
		if (strpos($definition, '=') !== false) {
			[ $definition, $value ] = explode('=', $definition);
		}

		$def = implode('=',
			[ $definition ]
			+ ($value === null ? [] : [ 1 => self::serializeValue($value) ])
		);

		if ($value !== null && $this->containsPrimitives()) {
			$definition = $value;
		}

		if ($existing = $this->findItem($def)) {
			return $existing;
		}

		if ($checkIfExistsOnly === true) {
			return null;
		}

		$uniqueKey = null;

		$found = $this->findCandidate($definition, static function($item) use ($definition, $value, & $uniqueKey) {

			if (! $item instanceof Node) {
				return $item;
			}

			if (! ($prop = $item->get($definition)) instanceof Property) {
				throw new InvalidNodeException(sprintf("Node '%s' is not a Property.", $prop->getPath()));
			}

			if ($prop instanceof Property && $prop->isUniqueKey()) {
				$uniqueKey = sprintf('%s=%s', $prop->getName(), is_array($value) ? implode(',', $value) : $value);
			}

			return $item->set($definition, $value);
		});

		if (! $found) {
			throw new ItemNotFoundException(sprintf("Option '%s' not found in '%s'.", $def, $this->getPath()));
		}

		$this->add($found);

		return $found instanceof Node
			? $found->setKey($uniqueKey)->updateParentalLinks()
			: $found;
	}


	/**
	 * @param string $def
	 * @return bool
	 */
	public function hasPicked(string $def): bool
	{
		return $this->findItem($def) !== null;
	}


	/**
	 * @param array|string $data
	 * @param bool         $ignoreNonExistingNodes
	 * @return Node
	 */
	public function initialize($data, bool $ignoreNonExistingNodes = false): Node
	{
		if ($this->containsPrimitives()) {

			if ($this->getCandidates()->isNotEmpty()) {
				foreach ($data as $item) {
					$this->pick($item->key);
				}
			}

			return $this;
		}

		$candidates = $this->getCandidates();

		foreach ($data as $properties) {

			/**
			 * @var ValueProvider $valueProvider
			 * @var Options $option
			 */

			$option = null;
			$properties = collect($properties);

			foreach ($candidates as $name => $valueProvider) {

				if (! $properties->has($name)) {
					continue;
				}

				$value = $properties->pull($name);

				if (($prop = $valueProvider->getProperty())
					&& $prop->isUniqueKey()
					&& ($o = $this->pick($name, $value)) instanceof Node) {

					$option = $o;
					continue;
				}

				if (! $option) {
					throw new UndeterminedPropertyException('No specific option candidate picked up. (ToDo: This issue has to be made up. )');
				}

				$option->set($name, $value);
			}

			if (! $option && $candidates->isNotEmpty()) {
				throw new UndeterminedPropertyException('No specific option candidate picked up. (ToDo: This issue has to be made up. )');
			}

			$this->fillNodeWithData($properties->all(), $option, $ignoreNonExistingNodes);
		}

		return $this;
	}


	/**
	 * @param Node $node
	 */
	public function remove(Node $node): void
	{
		foreach ($this->items as $key => $item) {
			if ($item === $node) {
				unset($this->items[$key]);
				$this->items = array_values($this->items);
				return;
			}
		}
	}


	public function clear(): void
	{
		$this->reset(true);
	}


	/**
	 * @param mixed $value
	 * @return string
	 */
	public static function serializeValue($value): string
	{
		return is_array($value) ? implode(',', $value) : (string) $value;
	}


	/**
	 * @return Collection
	 */
	protected function collection(): Collection
	{
		$collection = collect($this->getItems());

		if ($this->containsPrimitives()) {
			return $collection->map(static function(ArrayItem $item) {
				return [ 'key' => $item->getKey(), 'value' => $item->getValue() ];
			})
				->values();
		}

		return $collection;
	}


	/**
	 * @param string $by
	 * @return Node|ArrayItem|null
	 */
	private function findItem(string $by)
	{
		foreach ($this->items as $item) {
			if ($item->getKey() === $by) {
				return $item;
			}
		}

		return null;
	}


	/**
	 * @param string        $by
	 * @param callable|null $onSuccess
	 * @return Node|ArrayItem|null
	 * @internal
	 */
	private function findCandidate(string $by, callable $onSuccess = null)
	{
		foreach ($this->candidates as $candidate) {

			if (($candidate instanceof Node && $candidate->find($by))
				|| ($candidate instanceof ArrayItem && $candidate->getKey() === $by)) {

				return $onSuccess === null ? clone $candidate : $onSuccess(clone $candidate);
			}
		}

		return null;
	}


	/**
	 * @param array       $into
	 * @param mixed       $item
	 * @param string|null $key
	 * @return Options
	 */
	private function addItem(array & $into, $item, $key = null): Options
	{
		if ($item instanceof ArrayItem) {
			$key = $item->getKey();
		}

		$key = $this->sanitizeArrayKey($key);

		if (is_scalar($item)) {
			$item = new ArrayItem($item, $key);
		}

		if ($this->itemType === null) {
			$this->itemType = self::getItemType($item);

		} elseif ($this->itemType !== self::getItemType($item)) {
			throw new InvalidValueException(sprintf('Cannot mix types of items (%s vs. %s).', $this->itemType, gettype($item)));
		}

		if ($key !== null && $item instanceof Node) {
			throw new InvalidValueException('Cannot create array of objects with associative keys. Use Scheme::bag() instead.');
		}

		if ($item instanceof Node) {
			$item->setParent($this);
		}

		$into[] = $item;

		return $this;
	}


	private function validateDeclaration(): void
	{
		if ($this->containsPrimitives()) {
			return;
		}

		if (count($this->candidates) > 1) {
			throw new InvalidNodeException('Multiple candidates not supported.');
		}

		/** @var Node $bag */
		$bag = $this->candidates[0];

		$uniqueKeyCount = 0;

		foreach ($bag->getChildren() as $property) {
			if ($property instanceof Property) {
				$uniqueKeyCount += (int) $property->isUniqueKey();
			}
		}

		if ($uniqueKeyCount === 0) {
			throw new InvalidUniqueKeyException(sprintf("One of the properties in '%s' must be set as unique key. (Use uniqueKey() marker.)", $this->getPath()));

		}

		if ($uniqueKeyCount > 1) {
			throw new InvalidUniqueKeyException('Multiple unique keys are not implemented yet.');
		}
	}


	/**
	 * @param  mixed $key
	 * @return string|null
	 */
	private function sanitizeArrayKey($key): ?string
	{
		$key = is_string($key) && $key !== '' ? $key : null;

		if ($this->associative !== null && $this->associative !== (bool) $key) {
			throw new InvalidValueException('Cannot mix associative and non-associative keys.');
		}

		$this->associative = (bool) $key;

		return $key;
	}


	/**
	 * @param mixed $item
	 * @return string
	 */
	private static function getItemType($item): string
	{
		if ($item instanceof Node) {
			return 'node';
		}

		if ($item instanceof ArrayItem) {
			return gettype($item->getValue());
		}

		$mismatch = is_object($item) ? get_class($item) : gettype($item);
		throw new InvalidValueException(sprintf('%s is not a valid option item.', $mismatch));
	}


	/**
	 * @internal
	 * @param  mixed $values
	 * @return array
	 */
	private static function valuesFromProviderIfAny($values): array
	{
		if (is_array($values)) {
			return $values;
		}

		if ($values instanceof ManyValuesProvider) {
			return $values->getValues();
		}

		$mismatch = is_object($values) ? ('instance of ' . get_class($values)) : gettype($values);
		throw new InvalidValueException(sprintf('Items must be a static array or an instance of ManyValuesProvider, %s given.', $mismatch));
	}
}
