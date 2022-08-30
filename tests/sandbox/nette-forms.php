<?php

namespace Schemer\Tests\sandbox;

use Schemer\Extensions\FormsForSchemer;
use Schemer\Extensions\FormInputSpecification;
use Schemer\Bridges\SchemerNetteForms\NetteFormExtender;
use Schemer\Tests\Bootstrap;
use Schemer\Tests\schemes\Inquiry;
use Nette\Forms\Form;

require __DIR__ . '/../Bootstrap.php';

Bootstrap::boot();

//Debugger::enable();
//Debugger::$maxDepth = 6;

$scheme = Inquiry::buildScheme();

$form = new Form;

$inputs = (new FormsForSchemer)
	->fromScheme($scheme)

	// ToDo: Finish form inputs for existing nodes update
	/*->filter(function(FormInputSpecification $spec) {
		return $spec->getGroup() !== null;
	})
	->map(function(FormInputSpecification $spec) {
		if ($spec->getName() === 'prizeId' && $spec->getGroup() !== null) {
			$spec->setAsDisabled();
		}
	})*/

	->filter(function(FormInputSpecification $spec) {
		return $spec->getGroup() === null;
	})
	->extendForm(new NetteFormExtender($form));

$form->addSubmit('update', 'Update');

if ($form->isSuccess()) {
	$inputs->updateScheme($scheme, $form->getValues('array'));
}

$form->render();

//		$form .= sprintf(
//			'<input type="hidden" name="schemeId" value="%s">' .
//				'<input type="submit" name="update" value="Update scheme">',
//			$scheme->get('schemeId')->getValue()
//		);
//
//		echo $form . '</form>';

//	private static function handleSchemeUpdate(Node $scheme): void
//	{
//		$values = array_filter($_POST, function(& $value, $key) {
//			return strpos($key, SchemePathToInputNameTransformer::INPUT_PREFIX) === 0;
//		},ARRAY_FILTER_USE_BOTH);
//
//		$values = array_map(function($value) {
//			if (in_array($value, [ 'true', 'false' ], true)) {
//				$value = $value === 'true';
//			}
//			return $value;
//		}, $values);
//
//		(new FormsForSchemer)
//			->fromScheme($scheme)
//			->updateScheme($scheme, $values);
//
//		echo sprintf('<h3>Updated scheme %s</h3>', ucfirst($scheme->get('schemeId')->getValue()));
//
//		self::print($scheme);
//
//		exit;
//	}
