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
	private int $position;

	/** @var Property[] */
	private array $siblings = [];


	public function __construct(...$siblings)
	{
		parent::__construct();

		$this->position = 0;

		if (count($siblings) === 1 && is_array($siblings[0] ?? null)) {
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


	public function add(Node $child): Node
	{
		throw new BadMethodCallException('Group members must be defined in constructor.');
	}


	public function current(): ?Property
	{
		return $this->siblings[$this->position] ?? null;
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


	public function toArray(): array
	{
		return $this->siblings;
	}


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
