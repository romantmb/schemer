<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;


/**
 * Boolean user input validator
 */
final class BooleanInput extends NullableBooleanInput
{

	public function isValid(): bool
	{
		return is_bool($this->getValue());
	}


	public function isNullable(): bool
	{
		return false;
	}


	public function getIssue(): ?string
	{
		return match (true) {
			$this->isUndefined() => 'must be defined',
			$this->isEmpty()     => 'must not be null',
			default              => parent::getIssue(),
		};
	}
}
