<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;

use Generator;


final class Traverser
{

	private function __construct(private Node $node, private int $level = 1)
	{
	}


	public static function run(Node $node): Generator
	{
		return (new self($node))
			->traverse();
	}


	private function traverse(): Generator
	{
		if ($this->node instanceof Property) {
			yield $this->level => $this->node;

		} elseif ($this->node instanceof Options) {
			yield from $this->optionItems($this->node);
			yield from $this->optionCandidates($this->node);
		}

		foreach ($this->node->getChildren() as $child) {
			yield from (new self($child, $this->level + 1))
				->traverse();
		}
	}


	private function optionItems(Options $options): Generator
	{
		foreach ($options->getItems() as $picked) {
			if ($picked instanceof Node) {
				yield $this->level => $picked;
				yield from (new self($picked, $this->level + 1))
					->traverse();
			}
		}
	}


	private function optionCandidates(Options $options): Generator
	{
		foreach ($options->getCandidates() as $candidate) {
			if ($candidate instanceof ValueProvider && $property = $candidate->getProperty()) {
				yield $property;
			}
		}
	}
}
