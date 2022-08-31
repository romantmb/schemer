<?php

namespace Schemer\Tests\sandbox;

use Schemer\Tests\Bootstrap;
use Schemer\Tests\schemes\Inquiry;
use Schemer\Bridges\SchemerNetteForms\FormExtender;
use Schemer\Extensions\Forms\SchemeForm;
use Schemer\Extensions\Forms\SchemeFormFactory;
use Schemer\Extensions\FormsForSchemer;
use Nette\Forms\Form;

require __DIR__ . '/../Bootstrap.php';

Bootstrap::boot();

//Debugger::enable();
//Debugger::$maxDepth = 6;

$schemeFormFactory = (new SchemeFormFactory)
	->setFormExtender(new FormExtender);

$scheme = Inquiry::buildScheme();

// A) Basic way
/*$form =*/ SchemeForm::from($scheme)
	->into((new FormExtender)->extend(new Form))
	->modify(function(Form $form) {
		$form->addSubmit('update', 'Update');
	});

// B) Factory way (recommended)
$form = $schemeFormFactory
	->create($scheme, new Form)
	->modify(function(Form $form) {
		$form->addSubmit('update', 'Update');
	});

dumpe($form);

$form->render();
exit;


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

//	->filter(function(FormInputSpecification $spec) {
//		return $spec->getGroup() === null;
//	})
	->extendForm(new NetteFormExtender($form));

if ($form->isSuccess()) {
	$inputs->updateScheme($scheme, $form->getValues('array'));
	$form = new Form;
	(new FormsForSchemer)
		->fromScheme($scheme)
		->extendForm(new NetteFormExtender($form));
}

dump($inputs->fetchGrouped());

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
