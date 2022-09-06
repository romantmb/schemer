<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;


/**
 * Date/time input validator
 */
final class DateTimeInput extends BasicInput
{

	public function isValid(): bool
	{
		return ! $this->isEmpty() && parent::isValid();
	}


	public function getIssue(): ?string
	{
		return match (true) {
			$this->isUndefined() => 'must be defined',
			$this->isEmpty()     => 'must not be empty',
			default              => parent::getIssue(),
		};
	}
}
