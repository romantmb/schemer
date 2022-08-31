<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions;

use Schemer\Extensions\Forms\InputSpecification;
use Schemer\Exceptions\SchemerException;
use Illuminate\Support\Collection;


final class InputCollection extends Collection
{

	public function __construct(mixed $inputs = null)
	{
		parent::__construct($inputs);
	}


	public function getByName(string $name, string $groupHash = null): ?InputSpecification
	{
		$found = $this->filterBy(function(InputSpecification $spec) use ($name) {
			return $spec->getName() === $name;
		}, $groupHash);

		if ($found->count() > 1) {
			throw new SchemerException(sprintf("Multiple scheme inputs with name '%s' found in collection.", $name));
		}

		return $found->first();
	}


	public function getByPath(string $path): ?InputSpecification
	{
		$items = $this;

		if ($this->isGrouped()) {
			$items = $this->flatten();
		}

		return $items->filterBy(function(InputSpecification $spec) use ($path) {
			return $spec->getPath() === $path;
		})
			->first();
	}


	public function isGrouped(): bool
	{
		return $this->first() instanceof Collection;
	}


	protected function filterBy(callable $filter, string $groupHash = null): self
	{
		$items = $this;

		if ($this->isGrouped()) {
			if ($groupHash === null) {
				throw new SchemerException('Collection of scheme inputs is grouped, but group hash (as argument #2) is not defined.');
			}

			$items = $this->get($groupHash);
		}

		return $items->filter($filter);
	}
}
