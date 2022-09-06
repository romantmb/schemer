<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators;


/**
 * Interface of single user-input
 */
interface Input
{

	public function isValid(): bool;


	public function isUndefined(): bool;


	public function isNullable(): bool;


	public function isNull(): bool;


	public function isEmpty(): bool;


	public function getValue(bool $unmodified = false);


	public function getName(): ?string;


	public function getKey(): ?string;


	public function getIssue(): ?string;
}
