<?php
/**
 * @noinspection PhpUnhandledExceptionInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Schemer\Tests\Schemer;

use Schemer\Options;
use Schemer\Validators\Inputs\BooleanInput;
use Schemer\Validators\Inputs\TextualInput;
use Schemer\Node;
use Schemer\Scheme;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * @testCase
 */
final class SchemeTest extends TestCase
{

	public function testBasicInit(): void
	{
		$scheme = self::buildScheme();

		Assert::type( Options::class, $scheme );
		Assert::same( '[]', $scheme->toJson() );

		$scheme->pick('name', 'rating')
			->set('type', 'int');

		Assert::same( '[{"name":"rating","title":null,"type":"int","required":false,"unit":null}]', $scheme->toJson() );

		$scheme->pick('name', 'weight')
			->set('type', 'float')
			->set('unit', 'kg');

		Assert::same( '[{"name":"rating","title":null,"type":"int","required":false,"unit":null},{"name":"weight","title":null,"type":"float","required":false,"unit":"kg"}]', $scheme->toJson() );

		$scheme->pick('name', 'badge')
			->set('type', 'set');

		// intermezzo
		Assert::same(
			'kg',
			self::buildScheme()
				->initialize($scheme->toJson())
				->get('[name=weight].unit')
				->getValue()
		);

		/** @var Options $paramSet */
		$paramSet = $scheme->get('[name=badge].set');
		$paramSet
			->add('on-stock', 'Skladem')
			->add('on-demand', 'Na objednání')
			->add('n-a', 'Není dostupné');

		Assert::same(
			'[{"name":"rating","title":null,"type":"int","required":false,"unit":null},{"name":"weight","title":null,"type":"float","required":false,"unit":"kg"},{"name":"badge","title":null,"type":"set","set":[{"key":"Skladem","value":"on-stock"},{"key":"Na objedn\u00e1n\u00ed","value":"on-demand"},{"key":"Nen\u00ed dostupn\u00e9","value":"n-a"}],"required":false,"unit":null}]',
			$scheme->toJson()
		);

		$jsonToBeStored = $scheme->toJson();

		// new blank scheme
		$scheme = self::buildScheme();

		echo $jsonToBeStored;
		$scheme->initialize($jsonToBeStored);

		//echo $scheme->get('[name=badge].set')->toJson();
		echo $scheme->toJson();
	}


	private static function buildScheme(): Node
	{
		return Scheme::candidates('params',

			Scheme::bag(

				Scheme::prop('name', TextualInput::class)
					->uniqueKey(),

				Scheme::prop('title', TextualInput::class),

				Scheme::prop('type', [
					'int',
					'float',
					'string',
					'set',
				])
					->on('set', Scheme::group(

						Scheme::options('set')
					)),

				Scheme::prop('required', BooleanInput::class)
					->default(false),

				Scheme::prop('unit', TextualInput::class)
			)
		);
	}
}


(new SchemeTest())
	->run();
