<?php

namespace Schemer\Tests\sandbox;

use Schemer\Extensions\Forms\InputSpecification;
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
	->into((new FormExtender)->form(new Form), static function(Form $form) {
		// ...
	});

// B) Factory way (recommended)
$form = $schemeFormFactory
	->create($scheme, new Form, function(Form $form) {
		$form->addText('test')->setDefaultValue('Foo');
		$form->addSubmit('update', 'Update');
	})
	->groupedOnly()
	->modify('maxCountOfQueries', fn(InputSpecification $spec) => $spec->setAsDisabled())
	->onSubmit(function(SchemeForm $form) {
		dump($form->getValues());
	});

$form->render();

//if ($form->isSuccess()) {
//	$inputs->updateScheme($scheme, $form->getValues('array'));
//	$form = new Form;
//	(new FormsForSchemer)
//		->fromScheme($scheme)
//		->extendForm(new NetteFormExtender($form));
//}
