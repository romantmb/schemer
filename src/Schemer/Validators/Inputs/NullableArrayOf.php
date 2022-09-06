<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Validators\Inputs;

use Schemer\Validators\Input;


/**
 * Array of user inputs
 */
class NullableArrayOf implements Input
{
	/** @var Input[] */
	protected array $inputs = [];

	protected ?string $key;

	protected ?string $name;

	protected bool $undefined = true;

	protected ?array $values = null;


	public function __construct(Input $input)
	{
		$this->key = $input->getKey();
		$this->name = $input->getName();
		$this->undefined = $input->isUndefined();

		if (is_array($values = $input->getValue(true))) {
			$this->values = $values;
			$cls = get_class($input);
			foreach ($values as $value) {
				$this->inputs[] = new $cls($value, $input->getName());
			}
		}
	}


	public function isValid(): bool
	{
		foreach ($this->inputs as $input) {
			if (! $input->isValid()) {
				return false;
			}
		}
		return true;
	}


	public function isUndefined(): bool
	{
		return $this->undefined;
	}


	public function isNullable(): bool
	{
		return false;
	}


	public function isNull(): bool
	{
		return $this->values === null;
	}


	public function isEmpty(): bool
	{
		return ! $this->values;
	}


	public function getValue($unmodified = false): ?array
	{
		$set = [];
		foreach ($this->inputs as $input) {
			$set[] = $input->getValue($unmodified);
		}
		return $set ?: $this->values;
	}


	public function getName(): ?string
	{
		return $this->name;
	}


	public function getKey(): ?string
	{
		return $this->key;
	}


	public function getIssue(): ?string
	{
		foreach ($this->inputs as $index => $input) {
			if (($issue = $input->getIssue()) !== null) {
				return sprintf('(value at index #%s) %s', $index, $issue);
			}
		}
		return null;
	}
}
