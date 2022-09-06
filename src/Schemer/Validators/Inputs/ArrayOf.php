<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;


/**
 * Array of user inputs
 */
class ArrayOf extends NullableArrayOf
{

	public function isValid(): bool
	{
		return is_array($this->values) && parent::isValid();
	}


	public function isNullable(): bool
	{
		return true;
	}


	public function getIssue(): ?string
	{
		return $this->isNull() ? 'must be defined as array.' : parent::getIssue();
	}
}
