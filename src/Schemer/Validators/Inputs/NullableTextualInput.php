<?php /** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;

use Schemer\Support\Strings;
use InvalidArgumentException;


/**
 * Nullable textual user input validator
 *
 * @author Roman Pistek
 */
class NullableTextualInput extends BasicInput
{

	/**
	 * @param  mixed  $value
	 * @param  string $name
	 * @throws InvalidArgumentException
	 */
	public function __construct($value, $name = null)
	{
		parent::__construct($value, $name);

		if (is_string($this->value)) {
			$this->value = Strings::trim($this->value);
		}
	}


	/**
	 * @return bool
	 */
	function isValid(): bool
	{
		return is_string($this->value) || $this->isEmpty();
	}


	/**
	 * @return bool
	 */
	function isNullable(): bool
	{
		return true;
	}


	/**
	 * @return bool
	 */
	function isEmpty(): bool
	{
		return $this->isNull() || $this->value === '';
	}


	/**
	 * @param  bool $unmodified if true, original input value is returned
	 * @return mixed
	 */
	function getValue($unmodified = false)
	{
		return parent::getValue($unmodified) ?: null;
	}


	/**
	 * @return string|null
	 */
	function getIssue(): ?string
	{
		if (! $this->isValid()) {
			return sprintf('must be a string, %s given', gettype($this->value));
		}
		return null;
	}
}
