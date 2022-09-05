<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Forms;

use Schemer\Exceptions\ItemNotFoundException;
use Schemer\Extensions\Transformers\HumanReadableSlugTransformer;
use Schemer\Node;
use Schemer\Options;
use Schemer\Property;
use Schemer\StaticArrayProvider;
use Schemer\UserValueProvider;
use Schemer\ValueProvider;
use Schemer\Exceptions\SchemerException;
use InvalidArgumentException;
use Closure;


final class SchemeForm
{
	private InputCollection $collection;

	private ?FormExtender $formExtender = null;

	private HumanReadableSlugTransformer $humanReadableSlugTransformer;

	private array $fetched;

	private array $_mapBefore;

	private array $_mapAfter;

	private array $_filters = [];

	private ?Closure $_afterExtend = null;


	private function __construct(private Node $scheme)
	{
	}


	public static function from(Node $scheme): self
	{
		return new self($scheme);
	}


	public function into(mixed $formSource, callable $afterExtend = null): self
	{
		if (! $formSource) {
			throw new InvalidArgumentException('Argument must be an instance of form or FormExtender.');
		}

		if ($afterExtend) {
			$this->afterExtend($afterExtend);
		}

		if ($formSource instanceof FormExtender) {
			$this->formExtender = $formSource;
			return $this;
		}

		$this->formExtender
			?->with($formSource)
			?? throw new SchemerException('...');

		return $this;
	}


	public function getScheme(): Node
	{
		return $this->scheme;
	}


	public function render(...$args): void
	{
		$this->formExtender()->render(...$args);
	}


	public function isSuccess(): bool
	{
		return $this->formExtender()->isSuccess();
	}


	public function getValues(): array
	{
		return $this->formExtender()->getValues();
	}


	public function getErrors(): array
	{
		return $this->formExtender()->getErrors();
	}


	/**
	 * Validation before scheme update
	 */
	public function onValidate(callable $callback): self
	{
		return $this->delegateToExtender(__METHOD__, $callback);
	}


	/**
	 * If nothing changes, scheme is updated with submitted data
	 */
	public function onSubmit(callable $callback): self
	{
		return $this->delegateToExtender(__METHOD__, $callback);
	}


	/**
	 * All good after scheme update, great time to put updated scheme into storage
	 */
	public function onSuccess(callable $callback): self
	{
		return $this->delegateToExtender(__METHOD__, $callback);
	}


	/**
	 * If any error occurred
	 */
	public function onError(callable $callback): self
	{
		return $this->delegateToExtender(__METHOD__, $callback);
	}


	public function updateScheme(?Node $scheme = null): self
	{
		$scheme ??= $this->scheme;

		$uniqueKeys = collect();

		foreach ($this->getValues() as $id => $value) {

			if (! $spec = $this->collect()->get($id)) {
				throw new ItemNotFoundException(sprintf("Schemer form input with name '%s' not found.", $id));
			}

			$path = $spec->getPath();

			$uniqueKeys->map(function($value, $prop) use (& $path) {
				$path = str_replace("[$prop=*]", "[$prop=$value]", $path);
			});

			try {
//				if (is_callable($sanitizer)) {
//					$value = $sanitizer($spec, $value);
//				}
//
//				$this->validation($scheme, $spec, $value);

				if ((! is_string($value) || ! str_contains($value, '='))
					&& $spec->getProperty()->isUniqueKey()) {
					$value = sprintf('%s=%s', $spec->getName(), is_array($value) ? implode(',', $value) : $value);
				}

				if (($key = $scheme->set($path, $value)->getKey()) !== null) {
					call_user_func_array([ $uniqueKeys, 'put' ], explode('=', $key));
				}

			} catch (SchemerException $e) {
//				if ($this->_onError !== null) {
//					if (($this->_onError)($e, $spec, $value) === true) { continue; } // ignore error & proceed
//					return;
//				}
//
//				if ($this->formExtender !== null) {
//					$this->formExtender->addError($e->getMessage(), $id);
//					return;
//				}

				throw $e;
			}
		}

//		if ($this->_onSuccess !== null) {
//			($this->_onSuccess)($scheme);
//		}

		return $this;
	}


	public function modify(string $name, callable $modifier): self
	{
		$this->_mapBefore[$name] = Closure::fromCallable($modifier);
		return $this;
	}


	public function afterExtend(callable $callback): self
	{
		$this->_afterExtend = Closure::fromCallable($callback);
		return $this;
	}


	public function map(callable $after, ?callable $before): self
	{
		$this->flush();
		$this->_mapBefore[null] = $before ? Closure::fromCallable($before) : null;
		$this->_mapAfter[null] = Closure::fromCallable($after);
		return $this;
	}


	public function filter(callable $filter): self
	{
		$this->flush();
		$this->_filters[] = Closure::fromCallable($filter);
		return $this;
	}


	public function groupedOnly(): self
	{
		return $this->filter(fn(InputSpecification $spec) => $spec->getGroup() !== null);
	}


	public function notGroupedOnly(): self
	{
		return $this->filter(fn(InputSpecification $spec) => $spec->getGroup() === null);
	}


	public function collect(): InputCollection
	{
		return $this->collection ??= $this->dig($this->scheme)
			->filter($this->evalFilters())
			->mapWithKeys(fn(InputSpecification $spec) => [ $spec->getInputName() => $spec ]);
	}


	public function humanReadableSlug(string $slug): string
	{
		return isset($this->humanReadableSlugTransformer)
			? $this->humanReadableSlugTransformer::transform($slug)
			: $slug;
	}


	public function setHumanReadableSlugTransformer(string $class): self
	{
		$this->humanReadableSlugTransformer = new $class;
		return $this;
	}


	public function setFormExtender(FormExtender $extender): self
	{
		$this->formExtender = $extender;
		return $this;
	}


	private function delegateToExtender(string $method, ...$args): self
	{
		$method = substr($method, ($offset = strrpos($method, '::')) !== false ? ($offset + 2) : 0);
		$this->formExtender(extend: false)->$method(...$args);
		return $this;
	}


	private function formExtender(bool $extend = true): FormExtender
	{
		if (! isset($this->formExtender)) {
			throw new SchemerException(sprintf('Undefined FormExtender. Call %s::into() first.', self::class));
		}

		return $extend
			? $this->formExtender->extend(schemeForm: $this, onAfter: $this->_afterExtend)
			: $this->formExtender;
	}


	private function flush(): void
	{
		unset($this->fetched);
	}


	private function evalFilters(): callable
	{
		return fn(InputSpecification $spec) => collect($this->_filters)
			->sum(fn(callable $f) => (int) ($f($spec) === true)) === count($this->_filters);
	}


	private function dig(Node $node): InputCollection
	{
		$founds = new InputCollection;

		$finder = function(Node $node) use ($founds, & $finder) {

			if (empty($children = $node->getChildren())) {

				if ($node instanceof Options) {

					/** @var Property $candidateUniqueKeyProperty */
					$candidateUniqueKeyProperty = null;

					foreach ($node->getCandidates() as /*$name =>*/ $valueProvider) {
						/** @var ValueProvider $valueProvider */

						$property = $valueProvider->getProperty();

						$founds->push(
							(new InputSpecification($property, $this))
								->setPropertyWithUniqueKey($candidateUniqueKeyProperty)
						);

						if ($property?->isUniqueKey()) {
							$candidateUniqueKeyProperty = $property;
						}
					}

					foreach ($node->getItems() as $picked) {

						if ($picked instanceof Node) {
							$finder($picked);
						}
					}

				} elseif ($node instanceof Property) {

					if (($provider = $node->getValueProvider()) instanceof UserValueProvider
						|| $provider instanceof StaticArrayProvider) {

						$founds->push(new InputSpecification($node, $this));
					}
				}

				return;
			}

			foreach ($children as $child) {
				$finder($child);
			}
		};

		$finder($node);

		dump(
			collect($founds)
				->mapWithKeys(fn(InputSpecification $spec) => [ $spec->getName() => $spec->getPath() ])
				->all()
		);

		return $founds;
	}
}
