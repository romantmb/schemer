<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions;

use Schemer\Node;
use Schemer\Options;
use Schemer\Property;
use Schemer\ValueProvider;
use Schemer\StaticArrayProvider;
use Schemer\UserValueProvider;
use Schemer\Validators\UserInputValidator;
use Schemer\Exceptions\SchemerException;
use Schemer\Exceptions\InvalidValueException;
use Schemer\Exceptions\ItemNotFoundException;
use Schemer\Exceptions\InvalidUserInputException;
use League\Fractal\TransformerAbstract;
use InvalidArgumentException;
use Exception;


/**
 * @deprecated
 */
final class FormsForSchemer
{
	/** @var CollectionOfSchemeInputs */
	private $inputSpecs;

	/** @var array[CollectionOfSchemeInputs] */
	private $fetchedInputSpecs = [
		'grouped' => null,
		'flat'    => null,
	];

	/** @var string */
	private string $slugTransformerClass;

	/** @var callable */
	private $_filter;

	/** @var callable */
	private $mapper;

	/** @var FormExtender */
	private $formExtender;

	/** @var callable */
	private $onValidate;

	/** @var callable */
	private $_onError;

	/** @var callable */
	private $_onSuccess;


	/**
	 * @param string $class
	 * @return static
	 */
	public function setSlugToHumanReadableTransformer(string $class): FormsForSchemer
	{
		if (! class_exists($class)) {
			throw new InvalidArgumentException(sprintf("Class '%s' not found.", $class));
		}

		if (! is_a($class, TransformerAbstract::class, true)) {
			throw new InvalidArgumentException(sprintf("Class '%s' must extends '%s'.", $class, TransformerAbstract::class));
		}

		$this->slugTransformerClass = $class;

		return $this;
	}


	/**
	 * @throws SchemerException
	 */
	public function makeSlugHumanReadable(string $slug): string
	{
		return isset($this->slugTransformerClass) && class_exists($this->slugTransformerClass)
			? call_user_func([ $this->slugTransformerClass, 'transform' ], $slug)
			: $slug;
	}


	/**
	 * @param Node $scheme
	 * @return static
	 */
	public function fromScheme(Node $scheme): FormsForSchemer
	{
		$this->flush();

		$this->inputSpecs = $this->findEditableNodesInScheme($scheme);

		return $this;
	}


	/**
	 * @param callable $mapper
	 * @return $this
	 */
	public function map(callable $mapper): self
	{
		$this->flush();

		$this->mapper = $mapper;

		return $this;
	}


	/**
	 * @param callable $filter
	 * @return static
	 */
	public function filter(callable $filter): FormsForSchemer
	{
		$this->flush();

		$this->_filter = $filter;

		return $this;
	}


	/**
	 * @param FormExtender $extender
	 * @return static
	 */
	public function extendForm(FormExtender $extender): FormsForSchemer
	{
		$this->formExtender = $extender;

		$this->fetch()->each(function(FormInputSpecification $spec) {

			if ($spec->isHidden()) {
				$this->formExtender->addHidden($spec);

			} elseif ($spec->isSelect()) {
				$this->formExtender->addSelect($spec);

			} elseif ($spec->isMultiSelect()) {
				$this->formExtender->addCheckboxList($spec);

			} elseif ($spec->isSwitch()) {
				$this->formExtender->addSwitch($spec);

			} else {
				$this->formExtender->addText($spec);
			}
		});

		return $this;
	}


	/**
	 * @return CollectionOfSchemeInputs
	 */
	public function fetch(): CollectionOfSchemeInputs
	{
		if ($this->fetchedInputSpecs['flat'] === null) {

			if ($this->inputSpecs === null) {
				throw new SchemerException('Scheme is not defined, use fromScheme().');
			}

			$this->fetchedInputSpecs['flat'] = $this->fetchSpecs($this->inputSpecs)
				->filter($this->_filter)
				->map(function(FormInputSpecification $spec) {
					if ($this->mapper) {
						($this->mapper)($spec);
					}
					return $spec;
				});
		}

		return $this->fetchedInputSpecs['flat'];
	}


	/**
	 * @return CollectionOfSchemeInputs
	 */
	public function fetchGrouped(): CollectionOfSchemeInputs
	{
		if ($this->fetchedInputSpecs['grouped'] === null) {

			if ($this->inputSpecs === null) {
				throw new SchemerException('Scheme is not defined, use fromScheme().');
			}

			$this->fetchedInputSpecs['grouped'] = $this->inputSpecs
				->groupBy(function(FormInputSpecification $input) {
					return $input->getGroupHash();
				})
				->map(function(CollectionOfSchemeInputs $stepGroup) {
					return $this->fetchSpecs($stepGroup)
						->filter($this->_filter)
						->map(function(FormInputSpecification $spec) {
							if ($this->mapper) {
								($this->mapper)($spec);
							}
							return $spec;
						});
				})
				->filter(function(CollectionOfSchemeInputs $stepGroup) {
					return $stepGroup->isNotEmpty();
				});
		}

		return $this->fetchedInputSpecs['grouped'];
	}


	/**
	 * @param Node          $scheme
	 * @param array         $formValues
	 * @param callable|null $sanitizer
	 * @throws Exception
	 */
	public function updateScheme(Node $scheme, array $formValues, callable $sanitizer = null): void
	{
		$uniqueKeys = collect();

		foreach ($formValues as $id => $value) {

			if (! $spec = $this->getFetchedSpec($id)) {
				continue;
			}

			$path = $spec->getPath();

			$uniqueKeys->map(function($value, $prop) use (& $path) {
				$path = str_replace("[$prop=*]", "[$prop=$value]", $path);
			});

			try {
				if (is_callable($sanitizer)) {
					$value = $sanitizer($spec, $value);
				}

				$this->validation($scheme, $spec, $value);

				if ((! is_string($value) || strpos($value, '=') === false)
					&& $spec->getProperty()->isUniqueKey()) {
					$value = sprintf('%s=%s', $spec->getName(), is_array($value) ? implode(',', $value) : $value);
				}

				if (($key = $scheme->set($path, $value)->getKey()) !== null) {
					call_user_func_array([ $uniqueKeys, 'put' ], explode('=', $key));
				}

			} catch (SchemerException $e) {
				if ($this->_onError !== null) {
					if (($this->_onError)($e, $spec, $value) === true) { continue; } // ignore error & proceed
					return;
				}

				if ($this->formExtender !== null) {
					$this->formExtender->addError($e->getMessage(), $id);
					return;
				}

				throw $e;
			}
		}

		if ($this->_onSuccess !== null) {
			($this->_onSuccess)($scheme);
		}
	}


	/**
	 * @param callable $callback
	 * @return FormsForSchemer
	 */
	public function validate(callable $callback): FormsForSchemer
	{
		$this->onValidate = $callback;

		return $this;
	}


	/**
	 * @param callable $callback
	 * @return FormsForSchemer
	 */
	public function onError(callable $callback): FormsForSchemer
	{
		$this->_onError = $callback;

		return $this;
	}


	/**
	 * @param callable $callback
	 * @return FormsForSchemer
	 */
	public function onSuccess(callable $callback): FormsForSchemer
	{
		$this->_onSuccess = $callback;

		return $this;
	}


	/**
	 * @param Node                   $scheme
	 * @param FormInputSpecification $spec
	 * @param mixed                  $value
	 * @throws Exception
	 */
	private function validation(Node $scheme, FormInputSpecification $spec, $value): void
	{
		$property = $spec->getProperty();

		if (($provider = $property->getValueProvider()) && $provider instanceof UserValueProvider) {

			try {
				$validatorClass = $provider->getValidatorClass();
				UserInputValidator::simple(new $validatorClass($value, $spec->getName()));

			} catch (InvalidUserInputException $e) {
				if ($this->formExtender !== null) {
					$this->formExtender->addError($e->getMessage(), $spec->getInputName());
				}
				throw new InvalidValueException($e->getMessage(), 0, $e);
			}
		}

		if ($this->onValidate !== null) {
			($this->onValidate)($scheme, $spec, $value);
		}
	}


	/**
	 * @param Node $node
	 * @return CollectionOfSchemeInputs
	 */
	private function findEditableNodesInScheme(Node $node): CollectionOfSchemeInputs
	{
		$founds = new CollectionOfSchemeInputs;

		$finder = function(Node $node) use ($founds, & $finder) {

			if (empty($children = $node->getChildren())) {

				if ($node instanceof Options) {

					/** @var Property $candidateUniqueKeyProperty */
					$candidateUniqueKeyProperty = null;

					foreach ($node->getCandidates() as $name => $valueProvider) {
						/** @var ValueProvider $valueProvider */

						$property = $valueProvider->getProperty();

						$founds->push(
							(new FormInputSpecification($property, $this))
								->setPropertyWithUniqueKey($candidateUniqueKeyProperty)
						);

						if ($property && $property->isUniqueKey()) {
							$candidateUniqueKeyProperty = $property;
						}
					}

					foreach ($node->getItems() as $picked) {

						if ($picked instanceof Node) {
							$finder($picked);
						}
					}

				} elseif ($node instanceof Property) {

					if (($provider = $node->getValueProvider()) instanceof UserValueProvider
						|| $provider instanceof StaticArrayProvider) {

						$founds->push(new FormInputSpecification($node, $this));
					}
				}

				return;
			}

			foreach ($children as $child) {
				$finder($child);
			}
		};

		$finder($node);

		return $founds;
	}


	private function flush(): void
	{
		$this->fetchedInputSpecs = [ 'flat' => null, 'grouped' => null ];
	}


	/**
	 * @param CollectionOfSchemeInputs $specs
	 * @return CollectionOfSchemeInputs
	 */
	private function fetchSpecs(CollectionOfSchemeInputs $specs): CollectionOfSchemeInputs
	{
		return $specs->mapWithKeys(function(FormInputSpecification $spec) {
			return [ $spec->getInputName() => $spec ];
		});
	}


	/**
	 * @param string $id
	 * @return FormInputSpecification|null
	 */
	private function getFetchedSpec(string $id): ?FormInputSpecification
	{
		if (($spec = $this->fetch()->get($id)) === null) {
			if ($this->formExtender !== null) {
				$this->formExtender->addError(sprintf('Scheme form is corrupted.'));
			}
			throw new ItemNotFoundException(sprintf("Schemer form input with name '%s' not found.", $id));
		}

		return $spec;
	}
}
