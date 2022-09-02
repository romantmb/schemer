<?php

namespace Schemer\Tests\sandbox;

use Schemer\Tests\Bootstrap;
use Schemer\Tests\schemes\Inquiry;
use Schemer\Bridges\SchemerNetteForms\FormExtender;
use Schemer\Extensions\Forms\InputSpecification;
use Schemer\Extensions\Forms\SchemeForm;
use Schemer\Extensions\Forms\SchemeFormFactory;
use Nette\Forms\Form;

require __DIR__ . '/../Bootstrap.php';

Bootstrap::boot();

$schemeFormFactory = (new SchemeFormFactory)
	->setFormExtender(new FormExtender);

$scheme = Inquiry::buildScheme()
	->initialize($_GET['scheme'] ?? []);

// A) Basic way
/*$form =*/ SchemeForm::from($scheme)
	->into((new FormExtender)->with(new Form), static function(Form $form) {
		// ...
	});

// B) Factory way (recommended)
$form = $schemeFormFactory
	->create($scheme, new Form, function(Form $form) {
		$form->addSubmit('update', 'Update');
	})
	->notGroupedOnly()
	->modify('maxCountOfQueries', fn(InputSpecification $spec) => $spec->setAsDisabled())
	->onSubmit(function(SchemeForm $form) {
		$form->updateScheme();
	})
	->onSuccess(function(SchemeForm $form) {
		header('Location: ?scheme=' . urlencode($form->getScheme()->toJson()));
		exit;
	})
	->onError(function(SchemeForm $form) {
		dump($form->getErrors());
	});

$form->render();

printf('<pre>%s</pre>', $scheme->toJson(JSON_PRETTY_PRINT));
