<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators;


/**
 * Interface of single user-input
 *
 * @author Roman Pistek
 */
interface Input
{
	/**
	 * @return bool
	 */
	function isValid(): bool;


	/**
	 * @return bool
	 */
	function isUndefined(): bool;


	/**
	 * @return bool
	 */
	function isNullable(): bool;


	/**
	 * @return bool
	 */
	function isNull(): bool;


	/**
	 * @return bool
	 */
	function isEmpty(): bool;


	/**
	 * @param  bool $unmodified if true, modify() callback is ignored
	 * @return mixed
	 */
	function getValue($unmodified = false);


	/**
	 * @return string|null
	 */
	function getName(): ?string;


	/**
	 * @return string|null
	 */
	function getKey(): ?string;


	/**
	 * @return string
	 */
	function getIssue(): ?string;
}
