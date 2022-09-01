<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Forms;

use Schemer\Extensions\Transformers\HumanReadableSlugTransformer;
use Schemer\Node;
use Schemer\Exceptions\SchemerException;
use Closure;
use InvalidArgumentException;


final class SchemeForm
{
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
			?->form($formSource)
			?? throw new SchemerException('...');

		return $this;
	}


	public function onSubmit(callable $callback): self
	{
		$this->formExtender(extend: false)->onSubmit($callback);
		return $this;
	}


	public function isSuccess(): bool
	{
		return $this->formExtender()->isSuccess();
	}


	public function getValues(): array
	{
		return $this->formExtender()->getValues();
	}


	public function updateScheme(): self
	{

	}


	public function render(...$args): void
	{
		$this->formExtender()->render(...$args);
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


	public function ungroupedOnly(): self
	{
		return $this->filter(fn(InputSpecification $spec) => $spec->getGroup() === null);
	}


	public function collect(): InputCollection
	{
		// Finish...
		return new InputCollection;
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


	private function formExtender(bool $extend = true): FormExtender
	{
		if (! isset($this->formExtender)) {
			throw new SchemerException(sprintf('Undefined FormExtender. Call %s::into() first.', self::class));
		}

		return $extend
			? $this->formExtender->extend(with: $this, onAfter: $this->_afterExtend)
			: $this->formExtender;
	}


	private function form(): object
	{
		return $this->formExtender()->getForm()
			?? throw new SchemerException(sprintf('Undefined form. Call %s::into() first.', self::class));
	}


	private function flush(): void
	{
		unset($this->fetched);
	}
}
