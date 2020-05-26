<?php
/**
 * @noinspection PhpUnhandledExceptionInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Schemer\Tests\Schemer;

use Illuminate\Support\Collection;
use Schemer\ArrayItem;
use Schemer\Options;
use Schemer\Scheme;
use Tester;
use Tester\Assert;
use Tester\Expect;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * @testCase
 */
final class OptionsTest extends Tester\TestCase
{

	public function testBasics(): void
	{
		$o = Scheme::options('o');

		Assert::type( Options::class, $o );
		Assert::same( 'o', $o->getName() );
		Assert::false( $o->hasCandidates() );
		Assert::same( $o->getItems(), [] );
	}


	public function testTypes(): void
	{
		Assert::true( Scheme::options('o')->isBlank() );
		Assert::true( Scheme::options('o', null)->isBlank() );
		Assert::true( Scheme::options('o', [])->isBlank() );

		$o = Scheme::options('o', 1, 2, 3);

		Assert::true( $o->containsPrimitives() );
		Assert::false( $o->hasCandidates() );
		Assert::type( Collection::class, $o->getCandidates() );
		Assert::true( $o->getCandidates()->isEmpty() );

		$o = Scheme::options('o')
			->add('apple');

		Assert::false( $o->isBlank() );
		Assert::false( $o->hasCandidates() );
		Assert::equal(
			[ 0 => Expect::type(ArrayItem::class) ],
			$o->getItems()
		);
		Assert::same( [ 'apple' => 'apple' ], $o->getAsArray() );

		$o = Scheme::options('o', 1, 2, 3)
			->add([ 4, 5 ]);

		Assert::same( [ 1 => 1, 2, 3, 4, 5 ], $o->getAsArray() );

		$o = Scheme::options('o', [
			'prague' => 'Praha',
			'london' => 'Londýn'
		])
			->add([ 'paris' => 'Paříž' ]);

		//echo Tester\Dumper::toLine($emptyOptions->hasCandidates());

		echo "\r\n" . $o->toJson();
		echo "\r\n" . Tester\Dumper::toLine($o->getAsArray());
	}
}


(new OptionsTest())
	->run();
