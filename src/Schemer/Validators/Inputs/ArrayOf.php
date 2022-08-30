<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;


/**
 * Array of user inputs
 *
 * @author Roman Pistek
 */
class ArrayOf extends NullableArrayOf
{

	/**
	 * @return bool
	 */
	public function isValid(): bool
	{
		return is_array($this->values) && parent::isValid();
	}


	/**
	 * @return bool
	 */
	public function isNullable(): bool
	{
		return true;
	}


	/**
	 * @return string|null
	 */
	public function getIssue(): ?string
	{
		if ($this->isNull()) {
			return 'must be defined as array.';
		}
		return parent::getIssue();
	}
}
