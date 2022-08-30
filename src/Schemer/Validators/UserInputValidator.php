<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators;

use Schemer\Support\Strings;
use Schemer\Exceptions\InvalidUserInputException;
use Nette\Utils\Callback;
use InvalidArgumentException;
use Exception;


/**
 * Validates user inputs for further use, e.g. for storing in database
 *
 * <code>
 * use Schemer\Validators\UserInputValidator;
 * use Schemer\Validators\Inputs\TextualInput;
 * use Schemer\Validators\Inputs\NullableTextualInput;
 *
 *
 * echo UserInputValidator::simple(new TextualInput('  Hello, world! '));
 * // prints 'Hello, world!'
 *
 * echo UserInputValidator::simple(new TextualInput(null));
 * // throws Schemer\Exceptions\InvalidUserInputException 'Item must not be empty.'
 *
 * echo UserInputValidator::simple(new NullableTextualInput(123, 'title'));
 * // throws Schemer\Exceptions\InvalidUserInputException 'Title must be a string, integer given.'
 *
 *
 * // validation of user input array
 * $data = [
 *     'title' => ' First entity of all',
 *     'desc'  => 'Lorem ipsum...  ',
 *     'some'  => '',
 * ];
 * $data = UserInputValidator::scheme(
 *     new TextualInput([ $data, 'title' ]),
 *     new NullableTextualInput([ $data, 'desc' ], 'description')
 * )->fetchNotNulls();
 * // 'title' => 'First entity of all',
 * // 'desc'  => 'Lorem ipsum...',
 *
 *
 * // instantiated validator
 * $validator = new UserInputValidator;
 * $validator->setTranslator(ITranslator);
 *
 * $data = [
 *     'title' => 'Some title',
 *     'desc'  => null,
 * ];
 * list($title, $desc) = $validator->fetchNotNulls([
 *     new TextualInput([ $data, 'title' ]),
 *     new TextualInput([ $data, 'desc' ], 'description'),
 * ]);
 * // throws Schemer\Exceptions\InvalidUserInputException 'Description must not be empty.'
 * </code>
 *
 * @author Roman Pistek
 */
final class UserInputValidator
{
	/** @var Input[] */
	private $inputs = [];

	/** @var Exception|string */
	private $onInvalid;

	/** @var bool */
	private $convertKeysToSnakeCase = false;

	/** @var bool */
	private $ignoreUndefined = false;

	/** @var bool */
	public $singleInput = false;


	/**
	 * @param Input[] $inputs
	 * @throws InvalidArgumentException
	 */
	public function __construct(array $inputs = [])
	{
		foreach ($inputs as $input) {
			$this->addInput($input);
		}

		$this->onInvalid(null);
	}


	/**
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public static function scheme(): UserInputValidator
	{
		return new self(func_get_args());
	}


	/**
	 * @param Input $input
	 * @return static
	 * @throws Exception
	 */
	public static function simple(Input $input)
	{
		$self = new self([ $input ]);

		$self->singleInput = true;

		return $self->fetch();
	}


	/**
	 * Rejects data item, if callback returns true
	 *
	 * @param callable $callback called with argument Input
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function reject(callable $callback): UserInputValidator
	{
		if (! Callback::check($callback)) {
			throw new InvalidArgumentException('Callback function is not callable.');
		}

		$inputs = [];

		foreach ($this->inputs as $input) {
			if (! $callback($input)) {
				$inputs[] = $input;
			}
		}

		$this->inputs = $inputs;

		return $this;
	}


	/**
	 * Converts scheme keys to snake_case
	 *
	 * @return static
	 */
	public function snakeCaseKeys(): UserInputValidator
	{
		$this->convertKeysToSnakeCase = true;

		return $this;
	}


	/**
	 * Ignores undefined inputs in scheme
	 *
	 * @return static
	 */
	public function ignoreUndefined(): UserInputValidator
	{
		$this->ignoreUndefined = true;

		return $this;
	}


	/**
	 * Sets handler of invalid state
	 *
	 * @param Exception|string|null $handler
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function onInvalid($handler): UserInputValidator
	{
		if ($handler === null) {
			// default handler
			$handler = new InvalidUserInputException('%item% %issue%.');
		}

		if (! $handler instanceof Exception && ! is_string($handler)) {
			throw new InvalidArgumentException('Handler must be an error message (string) or an exception to be thrown.');
		}

		$this->onInvalid = $handler;

		return $this;
	}


	/**
	 * Returns validated data or false, if not valid
	 *
	 * @param Input[] $inputs
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	public function fetch(array $inputs = [])
	{
		if ($inputs) {
			return $this->setInputs($inputs)
				->fetch();
		}

		if (is_string($this->onInvalid)) {
			$this->onInvalid = new InvalidUserInputException($this->onInvalid);
		}

		$data = [];
		foreach ($this->inputs as $index => $input) {

			if ($this->ignoreUndefined && $input->isNullable() && $input->isUndefined()) {
				continue;
			}

			if ($input->isValid()) {
				if ($key = $input->getKey()) {
					if (array_key_exists($key, $data)) {
						$i = $index + 1; $_s = [ 1 => 'st', 'nd', 'rd']; $i .= ($i > 3 ? 'th' : $_s[ $i ]);
						$msg = sprintf("Ambiguous key '%s' for %s input item. Set input key explicitly with setKey().", $key, $i);
						throw new InvalidArgumentException($msg);
					}
					if ($this->convertKeysToSnakeCase) {
						$key = Strings::toSnakeCase($key);
						if (array_key_exists($key, $data)) {
							throw new InvalidArgumentException(sprintf("Snake-cased key %s is ambiguous.", $key));
						}
					}
					$data[$key] = $input->getValue();
				} else {
					$data[] = $input->getValue();
				}
				continue;
			}

			if ($this->onInvalid instanceof Exception) {
				$errorMsg = self::createErrorMessage($this->onInvalid->getMessage(), $input);
				$exceptionName = get_class($this->onInvalid);
				throw new $exceptionName($errorMsg);

			}

			return false;
		}

		return $this->singleInput ? @$data[0] : $data;
	}


	/**
	 * Returns validated data except items with null value
	 *
	 * @param Input[] $inputs
	 * @return array
	 * @throws Exception
	 */
	public function fetchNotNulls(array $inputs = [])
	{
		if ($inputs) {
			return $this->setInputs($inputs)
				->fetchNotNulls();
		}

		$this->reject(function(Input $input) {
			return $input->isNullable() && $input->isNull();
		});
		return $this->fetch($inputs);
	}


	/**
	 * @param  Input[] $inputs
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function setInputs(array $inputs = []): UserInputValidator
	{
		if ($this->inputs) {
			throw new InvalidArgumentException('Cannot redefine existing input scheme. Call fetch() without argument.');
		}

		$this->inputs = [];

		foreach ($inputs as $input) {
			$this->addInput($input);
		}

		return clone $this;
	}


	/**
	 * @param  Input $input
	 * @return static
	 * @internal
	 */
	private function addInput(Input $input): UserInputValidator
	{
		$this->inputs[] = $input;

		return $this;
	}


	/**
	 * @param string $pattern
	 * @param Input  $input
	 * @return string
	 */
	private static function createErrorMessage(string $pattern, Input $input): string
	{
		$msg = $pattern;
		$genName = ($key = $input->getKey()) ? "'$key'" : null;
		$itemName = $input->getName() ?: $genName ?: 'item';
		$msg = str_replace([ '%item%', '%issue%' ], [ $itemName, $input->getIssue() ], $msg);
		$msg = Strings::firstUpper($msg);
		return $msg;
	}
}
