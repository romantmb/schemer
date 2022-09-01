<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Forms;

use Schemer\Node;
use Schemer\Exceptions\SchemerException;


final class SchemeFormFactory
{
	private FormExtender $defaultFormExtender;


	public function create(Node $scheme, mixed $formSource = null, callable $afterExtend = null): SchemeForm
	{
		$schemeForm = SchemeForm::from($scheme)
			->setFormExtender($this->defaultFormExtender ??
				throw new SchemerException(sprintf(
					'FormExtender must be set with %s::setFormExtender().', self::class)));

		if ($formSource !== null) {
			$schemeForm->into($formSource);
		}

		$schemeForm->afterExtend($afterExtend);

		return $schemeForm;
	}


	public function setFormExtender(FormExtender $defaultFormExtender): self
	{
		$this->defaultFormExtender = $defaultFormExtender;
		return $this;
	}
}
