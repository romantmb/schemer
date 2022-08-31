<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Forms;

use Schemer\Exceptions\SchemerException;
use Schemer\Extensions\Transformers\HumanReadableSlugTransformer;
use Schemer\Node;
use InvalidArgumentException;


final class SchemeForm
{
	private ?FormExtender $formExtender = null;

	private HumanReadableSlugTransformer $humanReadableSlugTransformer;


	private function __construct(private Node $scheme)
	{
	}


	public static function from(Node $scheme): self
	{
		return new self($scheme);
	}


	public function into(mixed $formSource): self
	{
		if (! $formSource) {
			throw new InvalidArgumentException('Argument must be an instance of form or FormExtender.');
		}

		if ($formSource instanceof FormExtender) {
			$this->formExtender = $formSource;
			return $this;
		}

		$this->formExtender
			?->extend($formSource)
			?? throw new SchemerException('...');

		return $this;
	}


	public function modify(callable $modifier): self
	{
		$modifier($this->form());
		return $this;
	}


	public function render(...$args): void
	{
		$this->formExtender()->render(...$args);
	}


	public function setHumanReadableSlugTransformer(string $class): self
	{
		$this->humanReadableSlugTransformer = new $class;
		return $this;
	}


	public function humanReadableSlug(string $slug): string
	{
		return isset($this->humanReadableSlugTransformer)
			? $this->humanReadableSlugTransformer::transform($slug)
			: $slug;
	}


	public function setFormExtender(FormExtender $extender): self
	{
		$this->formExtender = $extender;
		return $this;
	}


	private function formExtender(): FormExtender
	{
		return $this->formExtender
			?? throw new SchemerException(sprintf('Undefined FormExtender. Call %s::into() first.', self::class));
	}


	private function form(): object
	{
		return $this->formExtender?->getForm()
			?? throw new SchemerException(sprintf('Undefined form. Call %s::into() first.', self::class));
	}
}
