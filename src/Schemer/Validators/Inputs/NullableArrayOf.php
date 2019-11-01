<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;

use Schemer\Validators\Input;


/**
 * Array of user inputs
 *
 * @author Roman Pistek
 */
class NullableArrayOf implements Input
{
	/** @var Input[] */
	protected $inputs = [];

	/** @var string */
	protected $key;

	/** @var string */
	protected $name;

	/** @var bool */
	protected $undefined = true;

	/** @var array|null */
	protected $values;


	/**
	 * @param Input $input
	 */
	public function __construct(Input $input)
	{
		$this->key = $input->getKey();

		$this->name = $input->getName();

		$this->undefined = $input->isUndefined();

		$values = $input->getValue(true);
		if (is_array($values)) {
			$this->values = $values;
			$cls = get_class($input);
			foreach ($values as $value) {
				$this->inputs[] = new $cls($value, $input->getName());
			}
		}
	}


	/**
	 * @return bool
	 */
	function isValid()
	{
		foreach ($this->inputs as $input) {
			if (!$input->isValid()) {
				return false;
			}
		}
		return true;
	}


	/**
	 * @return bool
	 */
	function isUndefined()
	{
		return $this->undefined;
	}


	/**
	 * @return bool
	 */
	function isNullable()
	{
		return false;
	}


	/**
	 * @return bool
	 */
	function isNull()
	{
		return $this->values === null;
	}


	/**
	 * @return bool
	 */
	function isEmpty()
	{
		return !$this->values;
	}


	/**
	 * @param  bool $unmodified if true, modify() callback is ignored
	 * @return mixed
	 */
	function getValue($unmodified = false)
	{
		$set = [];
		foreach ($this->inputs as $input) {
			$set[] = $input->getValue($unmodified);
		}
		return $set ?: $this->values;
	}


	/**
	 * @return string|null
	 */
	function getName()
	{
		return $this->name;
	}


	/**
	 * @return string|null
	 */
	function getKey()
	{
		return $this->key;
	}


	/**
	 * @return string|null
	 */
	function getIssue()
	{
		foreach ($this->inputs as $index => $input) {
			if (($issue = $input->getIssue()) !== null) {
				return sprintf('(value at index #%s) %s', $index, $issue);
			}
		}
		return null;
	}
}
