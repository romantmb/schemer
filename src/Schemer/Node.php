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
	/** @var Node */
	protected $parent;

	/** @var Node[] */
	protected $children = [];

	/** @var string */
	protected $key;


	/**
	 * @param Node|null $parent
	 */
	public function __construct(Node $parent = null)
	{
		$this->parent = $parent;
	}


	/**
	 * @param Node $child
	 * @return Node
	 * @throws ExistingPropertyNameException
	 * @throws InvalidNodeException
	 */
	public function add(Node $child): Node
	{
		if (! $child instanceof NamedNode) {
			$mismatch = is_object($child) ? ('instance of ' . get_class($child)) : strtolower(gettype($child));
			throw new InvalidNodeException(sprintf('Scheme node must implement NamedNode (e.g. Property or Options), %s given', $mismatch));
		}

		if (array_key_exists($child->getName(), $this->children)) {
			throw new ExistingPropertyNameException(sprintf("Property '%s' already exists in this node.", $child->getName()));
		}

		$child->beforeAdded($this);

		return $this->children[$child->getName()] = $child->setParent($this);
	}


	/**
	 * @param Node $parent
	 */
	public function beforeAdded(Node $parent): void
	{
	}


	/**
	 * @param  Node $parent
	 * @return Node
	 */
	public function setParent(Node $parent): Node
	{
		$this->parent = $parent;

		return $this;
	}


	/**
	 * @return Node|null
	 */
	public function getParent(): ?Node
	{
		return $this->parent;
	}


	/**
	 * @param bool $omitUndeterminedNodes
	 * @return array
	 * @throws UndeterminedPropertyException
	 */
	public function getChildren($omitUndeterminedNodes = true): array
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


	/**
	 * @param string $name
	 * @return NamedNode|null
	 */
	public function getChild(string $name): ?NamedNode
	{
		foreach ($this->getChildren() as $child) {
			if ($child instanceof NamedNode && $child->getName() === $name) {
				return $child;
			}
		}

		return null;
	}


	/**
	 * @param string $path
	 * @param bool   $setValues
	 * @return Node|ArrayItem|null
	 */
	public function find(string $path, $setValues = false)
	{
		$child = collect(explode('.', $path))->shift();
		if ($child) {
			$path = ltrim(Str::replaceFirst($child, '', $path), '.');
		}

		$pick = null;
		$child = Strings::replace($child, '/\[[^=]+(=[^]]*)?\].*/', function($matches) use (& $pick) {
			$pick = trim($matches[0], '[]');
			return '';
		});

		$value = null;
		if (strpos($child, '=') !== false) {
			[ $child, $value ] = explode('=', $child) + [ null, null ];
		}

		$current = $this;
		if ($child) {
			$current = $this->getChild($child);
			if ($current === null) {
				if ($pick) {
					if (strpos($pick, '=') !== false) {
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
	 * Strict form of find()
	 *
	 * @param string $path
	 * @param bool   $setValues
	 * @return Node|ArrayItem
	 */
	public function get(string $path, $setValues = false)
	{
		if ($item = $this->find($path, $setValues)) {
			return $item;
		}

		$scope = $this->getParent() === null ? '' : sprintf(" in '%s'", $this->getPath());
		$msg = $item === null ? sprintf("Item '%s' not found%s.", $path, $scope)
			: sprintf("Cannot set value of node '%s' (%s)", $item->getPath(), get_class($item));
		throw new ItemNotFoundException($msg);
	}


	/**
	 * @param string $path
	 * @param mixed  $value
	 * @return static
	 */
	public function set(string $path, $value): self
	{
		$item = $this->get($path, true);

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


	/**
	 * @param string $path
	 * @return Node|null
	 */
	public function tryFind(string $path): ?Node
	{
		try {
			return $this->find($path);

		} catch (ItemNotFoundException $e) {
			return null;
		}
	}


	/**
	 * Error-tolerant alternative of set()
	 *
	 * @param string $path
	 * @param mixed  $value
	 * @return Node
	 */
	public function trySet(string $path, $value): Node
	{
		try {
			$this->set($path, $value);

		} catch (ItemNotFoundException $e) {}

		return $this;
	}


	/**
	 * @param string $path
	 * @return Node
	 */
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


	/**
	 * @param string|array $data
	 * @param bool         $ignoreNonExistingNodes
	 * @return Node
	 */
	public function initialize($data, bool $ignoreNonExistingNodes = false): Node
	{
		$this->fillNodeWithData($data, null, $ignoreNonExistingNodes);

		return $this;
	}


	/**
	 * Error-tolerant alternative of initialize()
	 *
	 * @param string|array $data
	 * @return Node
	 */
	public function tryInitialize($data): Node
	{
		return $this->initialize($data, true);
	}


	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return
			($left =
				($this->getParent() ? $this->getParent()->getPath() : '')
				. ($this->getKey() ? sprintf('[%s]', $this->getKey()) : '')
			)
			. ($this instanceof NamedNode ? sprintf('%s%s', ($left ? '.' : ''), $this->getName()) : '');
	}


	/**
	 * @param string|null $key
	 * @return Node
	 */
	public function setKey(string $key = null): Node
	{
		if ($key !== null) {
			$this->key = $key;
		}

		return $this;
	}


	/**
	 * @return string
	 */
	public function getKey(): ?string
	{
		return $this->key;
	}


	/**
	 * Tries to find a key of any parent
	 *
	 * @return string|null
	 */
	public function getAnyKey(): ?string
	{
		if ($this->getKey()) {
			return $this->getKey();
		}

		if (! $this->getParent()) {
			return null;
		}

		return $this->getParent()->getAnyKey();
	}


	/**
	 * @return string
	 */
	public function getHash(): string
	{
		return substr(md5(spl_object_hash($this)), 0, 4);
	}


	/**
	 * @return array
	 * @throws UndeterminedPropertyException
	 */
	public function toArray(): array
	{
		return $this->collection()->toArray();
	}


	/**
	 * @param int $options
	 * @return string
	 * @throws UndeterminedPropertyException
	 */
	public function toJson($options = 0): string
	{
		return $this->collection()->toJson($options);
	}


	/**
	 * @param Node|null $node
	 * @return Node
	 */
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


	/**
	 * @param mixed     $data
	 * @param Node|null $node
	 * @param bool      $ignoreNonExistingNodes
	 */
	protected function fillNodeWithData($data, Node $node = null, bool $ignoreNonExistingNodes = false): void
	{
		$node = $node ?: $this;

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
	 * @return Collection
	 * @throws UndeterminedPropertyException
	 */
	protected function collection(): Collection
	{
		return collect($this->getChildren(false))
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


	/**
	 * @param string|null $name
	 * @param mixed|null  $value
	 * @return mixed
	 */
	private static function stripPropertyNameInValue(string $name = null, $value = null)
	{
		if (is_array($value)) {
			return collect($value)
				->mapWithKeys(static function($v, $k) use ($name) {
					return [ $k => self::stripPropertyNameInValue($name, $v) ];
				})
				->all();
		}

		return is_string($value) && strpos($value, "$name=") === 0
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
