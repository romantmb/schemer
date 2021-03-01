<?php

/**
 * Schemer
 * @author Roman Pistek
 */

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
	 * @param string      $message
	 * @param string|null $inputName
	 * @return mixed
	 */
	public function addError(string $message, string $inputName = null);
}
