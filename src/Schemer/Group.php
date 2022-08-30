<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;

use Schemer\Exceptions\InvalidNodeException;
use Iterator;
use BadMethodCallException;


/**
 * Group of sibling properties
 */
final class Group extends Node implements Iterator
{
	/** @var int */
	private $position;

	/** @var Property[] */
	private $siblings = [];


	/**
	 * @param mixed ...$siblings
	 */
	public function __construct(...$siblings)
	{
		parent::__construct();

		$this->position = 0;

		if (is_array($siblings) && count($siblings) === 1) {
			$siblings = $siblings[0];
		}

		foreach ($siblings as $sibling) {

			if (! $sibling instanceof NamedNode) {
				$mismatch = is_object($sibling) ? ('instance of ' . get_class($sibling)) : gettype($sibling);
				throw new InvalidNodeException(sprintf('Group member must implement NamedNode (e.g. Property or Options), %s given', $mismatch));
			}

			$this->siblings[] = $sibling;
		}
	}


	/**
	 * @param Node $child
	 * @return Node
	 */
	public function add(Node $child): Node
	{
		throw new BadMethodCallException('Group members must be defined in constructor.');
	}


	public function current(): ?Property
	{
		return @$this->siblings[$this->position];
	}


	public function next(): void
	{
		++$this->position;
	}


	public function key(): int
	{
		return $this->position;
	}


	public function valid(): bool
	{
		return isset($this->siblings[$this->position]);
	}


	public function rewind(): void
	{
		$this->position = 0;
	}


	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->siblings;
	}


	/**
	 * @param int $options
	 * @return string
	 */
	public function toJson($options = 0): string
	{
		throw new BadMethodCallException('Cannot export scheme group into JSON.');
	}


	public function __clone()
	{
		parent::__clone();

		foreach ($this->siblings as & $sibling) {
			$sibling = clone $sibling;
		}
	}
}
