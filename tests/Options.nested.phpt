<?php

/**
 * TEST: Options (nested)
 *
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Tests;

use Schemer\NamedNode;
use Schemer\Options;
use Schemer\Tests\schemes\Inquiry;
use Schemer\Exceptions\ItemNotFoundException;
use Schemer\Traverser;
use Tester\Assert;

require __DIR__ . '/Bootstrap.php';

Bootstrap::boot();


test('dummy inquiry', function() {

	$scheme = Inquiry::buildScheme();

	// defaults
	Assert::same( 1, $scheme->get('inquiry.settings.users.maxCountOfQueries')->getValue() );

	$scheme->set('inquiry.settings.users.maxCountOfQueries', 3);

	Assert::same( 3, $scheme->get('inquiry.settings.users.maxCountOfQueries')->getValue() );

	Assert::same(<<<JSON
{
    "inquiry": {
        "settings": {
            "users": {
                "maxCountOfQueries": 3
            }
        },
        "steps": []
    }
}
JSON,
		json($scheme)
	);

	// pick 'chooseOne' step (alternative way: ->pick('type=chooseOne') )
	$chooseOne = $scheme->get('inquiry.steps', Options::class)
		->pick('type', 'chooseOne');

	$chooseOne?->set('title', 'What is your favourite colour?');

	Assert::same(<<<JSON
{
    "inquiry": {
        "settings": {
            "users": {
                "maxCountOfQueries": 3
            }
        },
        "steps": [
            {
                "type": "chooseOne",
                "title": "What is your favourite colour?",
                "titleAfter": {
                    "onCorrect": null,
                    "onWrong": null
                },
                "prompt": {
                    "type": "options",
                    "options": []
                }
            }
        ]
    }
}
JSON,
		json($scheme)
	);

	Assert::exception(
		static fn() => $chooseOne?->get('prompts.options'),
		ItemNotFoundException::class,
		"Item 'inquiry.steps[type=chooseOne].prompts' not found.",
	);

	Assert::same(
		'inquiry.steps[type=chooseOne].prompt.options',
		$chooseOne?->get('prompt.options')->getPath()
	);

	($optionA = $chooseOne
		?->get('prompt.options', Options::class)
		->pick('key=a'))
		?->set('option', 'Red');

	Assert::same($output = <<<JSON
{
    "inquiry": {
        "settings": {
            "users": {
                "maxCountOfQueries": 3
            }
        },
        "steps": [
            {
                "type": "chooseOne",
                "title": "What is your favourite colour?",
                "titleAfter": {
                    "onCorrect": null,
                    "onWrong": null
                },
                "prompt": {
                    "type": "options",
                    "options": [
                        {
                            "key": "a",
                            "option": "Red",
                            "correct": null
                        }
                    ]
                }
            }
        ]
    }
}
JSON,
		json($scheme)
	);

	Assert::same(
		'Red',
		$scheme->get('inquiry.steps[type=chooseOne].prompt.options[key=a].option')->getValue()
	);

	Assert::null( $scheme->get('inquiry.steps[type=chooseOne].titleAfter.onCorrect')->getValue() );

	Assert::same(
		'inquiry.steps[type=chooseOne].titleAfter.onCorrect',
		$scheme->get('inquiry.steps[type=chooseOne].titleAfter.onCorrect')->getPath()
	);

	Assert::same(
		'inquiry.steps[type=chooseOne].prompt.type',
		$scheme->get('inquiry.steps[type=chooseOne].prompt.type')->getPath()
	);

	Assert::same(
		'inquiry.steps[type=chooseOne].prompt.options[key=a].correct',
		$scheme->get('inquiry.steps[type=chooseOne].prompt.options[key=a].correct')->getPath()
	);

	Assert::same(
		'inquiry.steps[type=chooseOne].prompt.options[key=a].correct',
		$optionA->get('correct')->getPath()
	);

	$optionB = $chooseOne?->get('prompt.options', Options::class)
		->pick('key', 'b');

	Assert::same(
		'inquiry.steps[type=chooseOne].prompt.options[key=b].option',
		$optionB?->get('option')->getPath()
	);

	Assert::null( $optionB?->get('option')->getValue() );

	$optionB?->set('option', 'Green');

	Assert::same(
		'Green',
		$scheme->get('inquiry.steps[type=chooseOne].prompt.options[key=b].option')->getValue()
	);

	// initialized scheme
	$restoredScheme = Inquiry::buildScheme()->initialize($output); // $output from line 100

	Assert::same(
		'inquiry.steps[type=chooseOne].prompt.options[key=a].correct',
		$restoredScheme->get('inquiry.steps[type=chooseOne].prompt.options[key=a].correct')->getPath()
	);

	foreach (Traverser::run($restoredScheme) as $level => $node) {
		printf(
			"%'-{$level}s %s (%s): %s\n", '-',
			$node instanceof NamedNode ? $node->getName() : '*',
			$node::class,
			$node->getPath(),
		);
	}
});
