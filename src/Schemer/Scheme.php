<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer;

use Schemer\Validators\Input;
use Schemer\Exceptions\InvalidValueException;


/**
 * Scheme builder
 */
class Scheme
{

	protected function __construct()
	{
	}


	/**
	 * Bag factory
	 */
	public static function bag(...$nodes): Node
	{
		return self::fillBag($nodes);
	}


	/**
	 * Property factory
	 */
	public static function prop(string $name, ...$content): Property
	{
		$property = new Property($name);

		if (empty($content)) {

			// property with null value
			return $property;
		}

		if (count($content) > 1
			|| (count($content) === 1 && $content[ 0 ] instanceof NamedNode)) {

			// property is a bag
			self::fillBag($content, $property);

			return $property;
		}

		return self::initializePropertyValue($property, $content[0]);
	}


	/**
	 * Static options factory
	 */
	public static function options(string $name, ...$content): Options
	{
		return (new Options($name))
			->setItems(count($content) === 1 && (is_array($content[0]) || $content[0] instanceof ManyValuesProvider) ? $content[0] : $content);
	}


	/**
	 * Variable options factory
	 */
	public static function candidates(string $name, ...$content): Options
	{
		return (new Options($name))
			->setCandidates(count($content) === 1 && (is_array($content[0]) || $content[0] instanceof ManyValuesProvider) ? $content[0] : $content);
	}


	/**
	 * Group of sibling properties factory (unlike a bag, it is not represented by a named property)
	 */
	public static function group(...$content): Group
	{
		return new Group($content);
	}


	private static function fillBag(array $children, Node $bag = null): Node
	{
		$bag ??= new Node;

		foreach ($children as $node) {
			$bag->add($node);
		}

		return $bag;
	}


	private static function initializePropertyValue(Property $property, mixed $value): Property
	{
		return match (true) {
			is_array($value)                          => $property->setOptionalValues(new StaticArrayProvider($value)),
			$value instanceof ManyValuesProvider      => $property->setOptionalValues($value),
			$value instanceof ValueProvider           => $property->setValue($value),
			is_string($value) && class_exists($value) => $property->setValue(new UserValueProvider($value)),
			is_scalar($value) || $value === null      => $property->setValue(new ScalarProvider($value)),
			default => throw new InvalidValueException(sprintf(
				'Property value must be primitive or ValueProvider implementation or %s implementation class name.',
				Input::class
			)),
		};
	}
}
