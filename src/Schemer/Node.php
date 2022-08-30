<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;

use Schemer\Exceptions\InvalidNodeException;
use Schemer\Exceptions\InvalidSchemeDataException;
use Schemer\Exceptions\ItemNotFoundException;
use Schemer\Exceptions\SchemerException;
use Schemer\Exceptions\UndeterminedPropertyException;
use Schemer\Exceptions\ExistingPropertyNameException;
use Nette\Utils\Strings;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Schemer\Support\Helpers;
use stdClass;


class Node implements Arrayable, Jsonable
{
	/** @var Node[] */
	protected array $children = [];

	protected ?string $key = null;


	public function __construct(protected ?Node $parent = null)
	{
	}


	/**
	 * @throws ExistingPropertyNameException
	 * @throws InvalidNodeException
	 */
	public function add(Node $child): Node
	{
		if (! $child instanceof NamedNode) {
			throw new InvalidNodeException(sprintf(
				'Scheme node must implement NamedNode (e.g. Property or Options), instance of %s given',
				$child::class));
		}

		if (array_key_exists($child->getName(), $this->children)) {
			throw new ExistingPropertyNameException(sprintf(
				"Property '%s' already exists in this node.", $child->getName()));
		}

		$child->beforeAdded($this);

		return $this->children[$child->getName()] = $child->setParent($this);
	}


	public function beforeAdded(Node $parent): void
	{
	}


	public function setParent(Node $parent): Node
	{
		$this->parent = $parent;
		return $this;
	}


	public function getParent(): ?Node
	{
		return $this->parent;
	}


	/**
	 * @throws UndeterminedPropertyException
	 */
	public function getChildren(bool $omitUndeterminedNodes = true): array
	{
		$children = [];

		foreach ($this->children as $child) {
			$children[] = $child;

			if ($child instanceof Property && $child->hasAnyConditionalSiblings()) {

				try {
					foreach ($child->getSiblings() as $sibling) {
						$children[] = $sibling;
					}

				} catch (UndeterminedPropertyException $e) {
					if ($omitUndeterminedNodes === false) {
						throw $e;
					}
				}
			}
		}

		return $children;
	}


	public function getChild(string $name): ?NamedNode
	{
		foreach ($this->getChildren() as $child) {
			if ($child instanceof NamedNode && $child->getName() === $name) {
				return $child;
			}
		}

		return null;
	}


	public function find(string $path, bool $setValues = false): Node|ArrayItem|null
	{
		$child = collect(explode('.', $path))->shift();
		if ($child) {
			$path = ltrim(Str::replaceFirst($child, '', $path), '.');
		}

		$pick = null;
		$child = Strings::replace($child, '/\[[^=]+(=[^]]*)?\].*/', static function($matches) use (& $pick) {
			$pick = trim($matches[0], '[]');
			return '';
		});

		$value = null;
		if (str_contains($child, '=')) {
			[ $child, $value ] = explode('=', $child) + [ null, null ];
		}

		$current = $this;
		if ($child) {
			$current = $this->getChild($child);
			if ($current === null) {
				if ($pick) {
					if (str_contains($pick, '=')) {
						throw new ItemNotFoundException(sprintf("Item '%s.%s[%s]' not found.", $this->getPath(), $child, $pick));
					}
					throw new ItemNotFoundException(sprintf("Item '%s.%s[%s]' not found. Did you mean '%s[%s=%s]'?", $this->getPath(), $child, $pick, $this->getPath(), $child, $pick));
				}
				throw new ItemNotFoundException(sprintf("Item '%s%s' not found.", $this->getPath() ? ($this->getPath() . '.') : '', $child));
			}
		}

		if ($setValues === true && $value !== null && $current instanceof NamedNodeWithValue) {
			$current->setValue($value);
		}

		if ($pick !== null) {
			if (! $current instanceof Options) {
				throw new InvalidNodeException(sprintf('Syntax [name=value] is for scheme options only, not for %s.', get_class($current)));
			}

			if ($setValues !== true && $current->pick($pick, null, true) === null) {
				throw new ItemNotFoundException(sprintf("Option '%s' in '%s' is not picked up. Try to use pick() or set().", $pick, $current->getPath()));
			}

			$current = $current->pick($pick);
		}

		if ($current === null) {
			return null;
		}

		return $path ? $current->find($path, $setValues) : $current;
	}


	/**
	 * Strict alternative to find()
	 */
	public function get(string $path, bool $setValues = false): Node|ArrayItem
	{
		if ($item = $this->find($path, $setValues)) {
			return $item;
		}

		$scope = $this->getParent() === null ? '' : sprintf(" in '%s'", $this->getPath());
		$msg = $item === null ? sprintf("Item '%s' not found%s.", $path, $scope)
			: sprintf("Cannot set value of node '%s' (%s)", $item->getPath(), get_class($item));
		throw new ItemNotFoundException($msg);
	}


	public function set(string $path, mixed $value): static
	{
		$item = $this->get($path, setValues: true);

		if ($item instanceof Property && $item->isUniqueKey() && ($options = $item->isInOptions())) {
			$lookFor = sprintf(
				'[%s=%s]',
				$item->getName(),
				self::stripPropertyNameInValue($item->getName(), Options::serializeValue($value))
			);

			if ($options->tryFind($lookFor) instanceof self) {
				throw new SchemerException(sprintf("Cannot redefine value of existing unique key '%s'.", $item->getPath()));
			}
		}

		if ($item instanceof Options) {
			return ($return = $item->pick($value)) instanceof self ? $return : $this;
		}

		if ($item instanceof NamedNodeWithValue) {
			$item->setValue(
				Helpers::sanitizeValue(
					self::stripPropertyNameInValue($item->getName(), $value)
				)
			);
		}

		return $this;
	}


	public function tryFind(string $path): ?Node
	{
		try {
			return $this->find($path);

		} catch (ItemNotFoundException) {
			return null;
		}
	}


	/**
	 * Error-tolerant alternative to set()
	 */
	public function trySet(string $path, mixed $value): Node
	{
		try {
			$this->set($path, $value);

		} catch (ItemNotFoundException) {}

		return $this;
	}


	public function unset(string $path): Node
	{
		$item = $this->get($path);

		if ($item instanceof Property && ($options = $item->isInOptions()) && $item->isUniqueKey()) {
			$options->remove($item->getParent());

		} elseif ($item instanceof NamedNodeWithValue) {
			$item->setValue(null);
		}

		return $this;
	}


	public function initialize(string|array $data, bool $ignoreNonExistingNodes = false): Node
	{
		$this->fillNodeWithData($data, null, $ignoreNonExistingNodes);

		return $this;
	}


	/**
	 * Error-tolerant alternative to initialize()
	 */
	public function tryInitialize(string|array $data): Node
	{
		return $this->initialize($data, ignoreNonExistingNodes: true);
	}


	public function getPath(): string
	{
		$parts = [
			$this->getParent()?->getPath() . $this->keyBadge(),
			$this instanceof NamedNode ? $this->getName() : null,
		];

		return implode('.', array_filter($parts));
	}


	public function setKey(?string $key = null): Node
	{
		$this->key = $key ?: null;
		return $this;
	}


	public function getKey(): ?string
	{
		return $this->key ?? null;
	}


	/**
	 * Tries to find a key of any parent
	 */
	public function getAnyKey(): ?string
	{
		return $this->getKey() ?? $this->getParent()?->getAnyKey();
	}


	public function getHash(): string
	{
		return substr(md5(spl_object_hash($this)), 0, 4);
	}


	/**
	 * @throws UndeterminedPropertyException
	 */
	public function toArray(): array
	{
		return $this->collection()->toArray();
	}


	/**
	 * @throws UndeterminedPropertyException
	 */
	public function toJson($options = 0): string
	{
		return $this->collection()->toJson($options);
	}


	protected function updateParentalLinks(Node $node = null): Node
	{
		$node = $node ?: $this;

		foreach ($node->getChildren() as $child) {
			if ($child instanceof self) {
				$child->setParent($node);
				$this->updateParentalLinks($child);
			}
		}

		return $node;
	}


	protected function fillNodeWithData(mixed $data, Node $node = null, bool $ignoreNonExistingNodes = false): void
	{
		$node ??= $this;

		if (is_string($data) && $node->getParent() === null) {
			try {
				$data = Json::decode($data);

			} catch (JsonException $e) {
				throw new InvalidSchemeDataException('JSON data for scheme initialization are corrupted.', 0, $e);
			}
		}

		if (! is_array($data) && (! is_object($data) || get_class($data) !== stdClass::class)) {
			$invalid = is_object($data) ? ('instance of ' . get_class($data)) : gettype($data);
			throw new InvalidSchemeDataException(sprintf('Data for scheme initialization must be JSON, array or stdClass, %s given.', $invalid), 0);
		}

		foreach ($data as $name => $content) {

			try {
				$item = $node->get($name);

			} catch (ItemNotFoundException $e) {
				if ($ignoreNonExistingNodes !== true) {
					throw $e;
				}
				continue;
			}

			if ($item instanceof Property && ! $item->isBag()) {
				$item->setValue($content);

			} else {
				$item->initialize($content, $ignoreNonExistingNodes);
			}
		}
	}


	/**
	 * @throws UndeterminedPropertyException
	 */
	protected function collection(): Collection
	{
		return collect($this->getChildren(omitUndeterminedNodes: false))
			->mapWithKeys(static function(Node $node) {
				if ($node instanceof NamedNode) {
					return [
						$node->getName() =>
							$node instanceof Property && ! $node->isBag() ? $node->getValue() : $node->toArray(),
					];
				}
				return [ $node->toArray() ];
			});
	}


	protected function keyBadge(): string
	{
		return $this->key ? "[$this->key]" : '';
	}


	private static function stripPropertyNameInValue(?string $name = null, mixed $value = null): mixed
	{
		if (is_array($value)) {
			return collect($value)
				->mapWithKeys(static function($v, $k) use ($name) {
					return [ $k => self::stripPropertyNameInValue($name, $v) ];
				})
				->all();
		}

		return is_string($value) && str_starts_with($value, "$name=")
			? substr($value, strlen("$name="))
			: $value;
	}


	public function __clone()
	{
		foreach ($this->children as & $child) {
			$child = clone $child;
		}
	}
}
