<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer;

use Schemer\Validators\Input;
use Schemer\Exceptions\InvalidValueException;
use LogicException;


/**
 * Scheme builder
 */
class Scheme
{

	public function __construct()
	{
		throw new LogicException('Scheme builder is a static class.');
	}


	/**
	 * Object factory
	 *
	 * @param  Property ...$nodes
	 * @return Node
	 */
	public static function bag(...$nodes): Node
	{
		return self::fillBag($nodes);
	}


	/**
	 * Property factory
	 *
	 * @param  string $name
	 * @param  mixed  $content
	 * @return Property
	 */
	public static function prop(string $name, ...$content)
	{
		$property = new Property($name);

		if (empty($content)) {
			// property with null value
			return $property;
		}

		if (sizeof($content) > 1 || sizeof($content) === 1 && $content[0] instanceof NamedNode) {

			// property is a bag
			self::fillBag($content, $property);
			return $property;
		}

		return self::initializePropertyValue($property, $content[0]);
	}


	/**
	 * Static options factory
	 *
	 * @param string $name
	 * @param array  $content
	 * @return Options
	 */
	public static function options(string $name, ...$content): Options
	{
		return (new Options($name))
			->setItems(sizeof($content) === 1 && (is_array($content[0]) || $content[0] instanceof ManyValuesProvider) ? $content[0] : $content);
	}


	/**
	 * Variable options factory
	 *
	 * @param string $name
	 * @param array  $content
	 * @return Options
	 */
	public static function candidates(string $name, ...$content): Options
	{
		return (new Options($name))
			->setCandidates(sizeof($content) === 1 && (is_array($content[0]) || $content[0] instanceof ManyValuesProvider) ? $content[0] : $content);
	}


	/**
	 * @param  mixed ...$content
	 * @return Group
	 */
	public static function group(...$content)
	{
		return new Group($content);
	}


	/**
	 * @param  array     $children
	 * @param  Node|null $bag
	 * @return Node
	 */
	private static function fillBag(array $children, Node $bag = null): Node
	{
		if ($bag === null) {
			$bag = new Node;
		}

		foreach ($children as $node) {
			$bag->add($node);
		}

		return $bag;
	}


	/**
	 * @param  Property $property
	 * @param  mixed    $value
	 * @return mixed
	 */
	private static function initializePropertyValue(Property $property, $value)
	{
		if (is_array($value)) {
			return $property->setOptionalValues(new StaticArrayProvider($value));
		}

		if ($value instanceof ManyValuesProvider) {
			return $property->setOptionalValues($value);
		}

		if ($value instanceof ValueProvider) {
			return $property->setValue($value);
		}

		if (is_string($value) && class_exists($value)) {
			return $property->setValue(new UserValueProvider($value));
		}

		if (is_scalar($value) || $value === null) {
			return $property->setValue(new ScalarProvider($value));
		}

		throw new InvalidValueException(sprintf('Property value must be primitive or ValueProvider implementation or %s implementation class name.', Input::class));
	}
}
