<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators;

use Schemer\Support\Strings;
use Schemer\Exceptions\InvalidUserInputException;
use InvalidArgumentException;
use Throwable;


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
	private array $inputs;

	private Throwable|string|null $onInvalid;

	private bool $convertKeysToSnakeCase = false;

	private bool $ignoreUndefined = false;

	public bool $singleInput = false;


	public function __construct(array $inputs = [])
	{
		$this->inputs = array_map(static fn(Input $input) => $input, $inputs);
		$this->onInvalid(null);
	}


	public static function scheme(...$args): self
	{
		return new self($args);
	}


	/**
	 * @throws Throwable
	 */
	public static function simple(Input $input): mixed
	{
		$self = new self([ $input ]);
		$self->singleInput = true;
		return $self->fetch();
	}


	/**
	 * Rejects data item, if callback returns true
	 */
	public function reject(callable $callback): self
	{
		$this->inputs = array_filter($this->inputs, static fn(Input $input) => ! $callback($input));
		return $this;
	}


	public function snakeCaseKeys(): self
	{
		$this->convertKeysToSnakeCase = true;
		return $this;
	}


	/**
	 * Ignores undefined inputs in scheme
	 */
	public function ignoreUndefined(): self
	{
		$this->ignoreUndefined = true;
		return $this;
	}


	/**
	 * Sets handler of invalid state
	 */
	public function onInvalid(Throwable|string|null $handler): self
	{
		if ($handler === null) {
			// default handler
			$handler = new InvalidUserInputException('%item% %issue%.');
		}

		if (! $handler instanceof Throwable && ! is_string($handler)) {
			throw new InvalidArgumentException('Handler must be an error message (string) or an exception to be thrown.');
		}

		$this->onInvalid = $handler;
		return $this;
	}


	/**
	 * Returns validated data or false, if not valid
	 *
	 * @throws Throwable
	 */
	public function fetch(array $inputs = []): mixed
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

			if ($this->onInvalid instanceof Throwable) {
				$errorMsg = self::createErrorMessage($this->onInvalid->getMessage(), $input);
				$exceptionName = get_class($this->onInvalid);
				throw new $exceptionName($errorMsg);
			}

			return false;
		}

		return $this->singleInput ? ($data[0] ?? null) : $data;
	}


	/**
	 * Returns validated data except items with null value
	 *
	 * @throws Throwable
	 */
	public function fetchNotNulls(array $inputs = []): array|bool
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


	public function setInputs(array $inputs = []): self
	{
		if (! empty($this->inputs)) {
			throw new InvalidArgumentException('Cannot redefine existing input scheme. Call fetch() without argument.');
		}

		$this->inputs = array_map(static fn(Input $input) => $input, $inputs);
		return clone $this;
	}


	private static function createErrorMessage(string $pattern, Input $input): string
	{
		$re = [
			'%item%'  => $input->getName() ?? (($key = $input->getKey()) ? "'$key'" : null) ?? 'item',
			'%issue%' => $input->getIssue(),
		];
		return Strings::firstUpper(str_replace(array_keys($re), array_values($re), $pattern));
	}
}
