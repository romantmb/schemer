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
	function isValid();


	/**
	 * @return bool
	 */
	function isUndefined();


	/**
	 * @return bool
	 */
	function isNullable();


	/**
	 * @return bool
	 */
	function isNull();


	/**
	 * @return bool
	 */
	function isEmpty();


	/**
	 * @param  bool $unmodified if true, modify() callback is ignored
	 * @return mixed
	 */
	function getValue($unmodified = false);


	/**
	 * @return string|null
	 */
	function getName();


	/**
	 * @return string|null
	 */
	function getKey();


	/**
	 * @return string
	 */
	function getIssue();
}
