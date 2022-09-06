<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;

use Nette\Utils\DateTime;
use Throwable;


/**
 * Nullable date/time input validator
 */
class NullableDateTimeInput extends BasicInput
{

	public function isValid(): bool
	{
		try {
			return DateTime::from($this->value) || $this->isEmpty();
		} catch (Throwable) {
			return false;
		}
	}


	public function isNullable(): bool
	{
		return true;
	}


	public function isEmpty(): bool
	{
		return $this->isNull() || ! $this->value;
	}


	public function getIssue(): ?string
	{
		return ! $this->isValid()
			? sprintf('must represent date and/or time, %s given', self::mismatch($this->value))
			: null;
	}


	private static function mismatch(mixed $value): string
	{
		return match (true) {
			is_int($value)    => $value,
			is_string($value) => sprintf("'%s'", $value),
			is_object($value) => sprintf('instance of %s', get_class($value)),
			default           => gettype($value),
		};
	}
}
