<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;

use Schemer\Validators\Input;
use Schemer\Support\Strings;
use Schemer\Exceptions\InvalidUserInputException;
use Closure;


/**
 * Basic user input validator
 */
abstract class BasicInput implements Input
{
	protected ?string $key = null;

	protected bool $undefined = false;

	protected ?Closure $outputModifier = null;


	/**
	 * @param  mixed       $value to be validated
	 * @param  string|null $name  of input, e.g. 'title' (optional)
	 */
	public function __construct(protected mixed $value, protected ?string $name = null)
	{
		$this->name = (string) $name ?: null;

		if (is_array($value) && count($value) === 2
			&& ($def = array_values($value)) && is_array($def[0]) && is_string($def[1])) {

			[ $data, $key ] = $value;

			// check all typo variants (original, snake-cased, camel-cased)
			foreach ([ $key, Strings::toCamelCase($key), Strings::toSnakeCase($key) ] as $keyVariant) {
				if (array_key_exists($keyVariant, $data)) {
					$this->key = $key;
					break;
				}
			}

			if ($this->key === null) {
				$this->key = $key;
				$this->undefined = true;
			}

			$this->value = $data[$keyVariant ?? null] ?? null;
		}

		if ($this->value !== null && ! is_scalar($this->value) && ! is_array($this->value)) {
			throw new InvalidUserInputException(sprintf('Supported types of value are nulls, scalars and arrays of scalars, %s given.', gettype($value)));
		}
	}


	/**
	 * Modifies value on output
	 */
	public function modify(callable|string $callback): static
	{
		if (is_string($callback)) {
			$callback = static fn($value) => call_user_func(
				[ Strings::class, $callback], $value, ...array_slice(func_get_args(), 1)
			);
		}
		$this->outputModifier = Closure::fromCallable($callback);
		return $this;
	}


	public function setKey(string|int|null $key): static
	{
		$this->key = (string) $key ?: null;
		return $this;
	}


	public function isValid(): bool
	{
		return ! $this->isUndefined();
	}


	public function isUndefined(): bool
	{
		return $this->undefined;
	}


	public function isNullable(): bool
	{
		return false;
	}


	public function isNull(): bool
	{
		return $this->value === null;
	}


	public function isEmpty(): bool
	{
		return ! $this->value || $this->isUndefined();
	}


	public function getValue(bool $unmodified = false): mixed
	{
		return match ($unmodified) {
			true  => $this->value,
			false => ($this->outputModifier ?? static fn($value) => $value)($this->value),
		} ?: null;
	}


	public function getName(): ?string
	{
		return $this->name;
	}


	public function getKey(): ?string
	{
		return $this->key;
	}


	public function getIssue(): ?string
	{
		return null;
	}
}
