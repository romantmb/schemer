<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Tests\schemes;

use Schemer\Node;
use Schemer\Validators\Inputs\NullableBooleanInput;
use Schemer\Validators\Inputs\NullableNumericInput;
use Schemer\Validators\Inputs\NullableTextualInput;
use Schemer\Validators\Inputs\BooleanInput;
use Schemer\Validators\Inputs\NumericInput;
use Schemer\Validators\Inputs\TextualInput;
use function Schemer\b; // bag
use function Schemer\g; // group
use function Schemer\p; // property
use function Schemer\c; // candidates


final class Inquiry
{

	public static function buildScheme(): Node
	{
		return b(
			p('inquiry',
				p('settings',
					p('users',
						p('maxCountOfQueries', NumericInput::class)
							->default(1),
						p('sendHello', BooleanInput::class)
							->default(false)
					)
				),

				c('steps',
					b(
						p('type', [ 'typeText', 'chooseOne' ])
							->uniqueKey()

							->on('typeText', g(
								p('title', NullableTextualInput::class)
									->default('Type something'),
								p('titleAfter', NullableTextualInput::class)
									->default('Your text'),
								p('prompt',
									p('type', 'text'),
									p('maxLength', NullableNumericInput::class)
								)
							))

							->on('chooseOne', g(
								p('title', NullableTextualInput::class),
								p('titleAfter',
									p('onCorrect', NullableTextualInput::class),
									p('onWrong', NullableTextualInput::class)
								),
								p('prompt',
									p('type', 'options'),
									c('options',
										b(
											p('key', TextualInput::class)
												->uniqueKey(),
											p('option', TextualInput::class),
											p('correct', NullableBooleanInput::class),
										)
									)
								)
							))
					)
				)
			)
		);
	}
}
