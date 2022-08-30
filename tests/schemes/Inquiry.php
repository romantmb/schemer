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
use Schemer\Validators\Inputs\NumericInput;
use Schemer\Validators\Inputs\TextualInput;
use function Schemer\bag;
use function Schemer\group;
use function Schemer\property;
use function Schemer\candidates;


final class Inquiry
{

	public static function buildScheme(): Node
	{
		return bag(
			property('inquiry',
				property('settings',
					property('users',
						property('maxCountOfQueries', NumericInput::class)
							->default(1)
					)
				),

				candidates('steps',
					bag(
						property('type', [ 'typeText', 'chooseOne' ])
							->uniqueKey()

							->on('typeText', group(
								property('title', NullableTextualInput::class)
									->default('Type something'),
								property('titleAfter', NullableTextualInput::class)
									->default('Your text'),
								property('prompt',
									property('type', 'text'),
									property('maxLength', NullableNumericInput::class)
								)
							))

							->on('chooseOne', group(
								property('title', NullableTextualInput::class),
								property('titleAfter',
									property('onCorrect', NullableTextualInput::class),
									property('onWrong', NullableTextualInput::class)
								),
								property('prompt',
									property('type', 'options'),
									candidates('options',
										bag(
											property('key', TextualInput::class)
												->uniqueKey(),
											property('option', TextualInput::class),
											property('correct', NullableBooleanInput::class),
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
