<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions;


interface FormExtender
{

	/**
	 * @param FormInputSpecification $spec
	 */
	public function addSelect(FormInputSpecification $spec);


	/**
	 * @param FormInputSpecification $spec
	 */
	public function addCheckboxList(FormInputSpecification $spec);


	/**
	 * @param FormInputSpecification $spec
	 */
	public function addSwitch(FormInputSpecification $spec);


	/**
	 * @param FormInputSpecification $spec
	 */
	public function addText(FormInputSpecification $spec);


	/**
	 * @param FormInputSpecification $spec
	 */
	public function addHidden(FormInputSpecification $spec);


	/**
	 * @param string      $message
	 * @param string|null $inputName
	 * @return mixed
	 */
	public function addError(string $message, string $inputName = null);
}
