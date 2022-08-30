<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions;

use Illuminate\Support\Collection;
use Schemer\Exceptions\SchemerException;


class CollectionOfSchemeInputs extends Collection
{

	/**
	 * @param mixed $inputs
	 */
	public function __construct($inputs = null)
	{
		parent::__construct($inputs);
	}


	/**
	 * @param string      $name
	 * @param string|null $groupHash
	 * @return FormInputSpecification|null
	 */
	public function getByName(string $name, string $groupHash = null): ?FormInputSpecification
	{
		$found = $this->filterBy(function(FormInputSpecification $spec) use ($name) {
			return $spec->getName() === $name;
		}, $groupHash);

		if ($found->count() > 1) {
			throw new SchemerException(sprintf("Multiple scheme inputs with name '%s' found in collection.", $name));
		}

		return $found->first();
	}


	/**
	 * @param string $path
	 * @return FormInputSpecification|null
	 */
	public function getByPath(string $path): ?FormInputSpecification
	{
		$items = $this;

		if ($this->isGrouped()) {
			$items = $this->flatten();
		}

		return $items->filterBy(function(FormInputSpecification $spec) use ($path) {
			return $spec->getPath() === $path;
		})
			->first();
	}


	/**
	 * @return bool
	 */
	public function isGrouped(): bool
	{
		return $this->first() instanceof Collection;
	}


	/**
	 * @param callable    $filter
	 * @param string|null $groupHash
	 * @return CollectionOfSchemeInputs
	 */
	protected function filterBy(callable $filter, string $groupHash = null): CollectionOfSchemeInputs
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
