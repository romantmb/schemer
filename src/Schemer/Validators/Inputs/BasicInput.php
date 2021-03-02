<?php /** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Validators\Inputs;

use Schemer\Validators\Input;
use Schemer\Support\Strings;
use Schemer\Exceptions\InvalidUserInputException;
use Nette\Utils\Callback;
use Nette\InvalidArgumentException;


/**
 * Basic user input validator
 *
 * @author Roman Pistek
 */
abstract class BasicInput implements Input
{
	/** @var mixed */
	protected $value;

	/** @var string */
	protected $name;

	/** @var string */
	protected $key;

	/** @var bool */
	protected $undefined = false;

	/** @var callable */
	protected $outputModifier;


	/**
	 * @param mixed  $value to be validated
	 * @param string $name of input, e.g. 'title' (optional)
	 */
	public function __construct($value, $name = null)
	{
		if (is_array($value) && count($value) === 2
			&& ($def = array_values($value)) && is_array($def[0]) && is_string($def[1])) {

			list($data, $key) = $value;

			// check all typo variants (original, snake-cased, camel-cased)
			$keyVariant = null;
			foreach ([ $key, Strings::toCamelCase($key), Strings::toSnakeCase($key) ] as $keyVariant) {
				if (array_key_exists($keyVariant, $data)) {
					$this->key = $key;
					break;
				}
			}

			if ($this->key === null) {
				$this->key = $key;
				$this->undefined = true;
			}

			$value = @$data[$keyVariant];
		}

		if ($value !== null && ! is_scalar($value) && ! is_array($value)) {
			throw new InvalidUserInputException(sprintf('Supported types of value are nulls, scalars and arrays of scalars, %s given.', gettype($value)));
		}

		$this->value = $value;
		$this->name = (string) $name ?: null;
	}


	/**
	 * Modifies value on output
	 *
	 * @param  mixed $callback callable or Nette\Utils\Strings filter name
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function modify($callback)
	{
		if (is_string($callback)) {
			$args = array_slice(func_get_args(), 1);
			$callback = static function($value) use ($callback, $args) {
				$func = [ 'Nette\Utils\Strings', $callback ];
				Callback::check($func);
				return call_user_func_array($func, array_merge([ $value ], $args));
			};
		}
		Callback::check($callback);
		$this->outputModifier = $callback;
		return $this;
	}


	/**
	 * @param  string $key
	 * @return static
	 */
	public function setKey($key)
	{
		$this->key = (string) $key;
		return $this;
	}


	/**
	 * @return bool
	 */
	function isValid(): bool
	{
		return ! $this->isUndefined();
	}


	/**
	 * @return bool
	 */
	function isUndefined(): bool
	{
		return $this->undefined;
	}


	/**
	 * @return bool
	 */
	function isNullable(): bool
	{
		return false;
	}


	/**
	 * @return bool
	 */
	function isNull(): bool
	{
		return $this->value === null;
	}


	/**
	 * @return bool
	 */
	function isEmpty(): bool
	{
		return ! $this->value || $this->isUndefined();
	}


	/**
	 * @param  bool $unmodified if true, original input value is returned
	 * @return mixed
	 */
	function getValue($unmodified = false)
	{
		$modifier = $this->outputModifier ?: static function($value) { return $value; };
		return $unmodified ? $this->value : $modifier($this->value);
	}


	/**
	 * @return string|null
	 */
	function getName(): ?string
	{
		return $this->name;
	}


	/**
	 * @return string|null
	 */
	function getKey(): ?string
	{
		return $this->key ?: null;
	}


	/**
	 * @return string|null
	 */
	function getIssue(): ?string
	{
		return null;
	}
}
