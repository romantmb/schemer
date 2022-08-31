<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Forms;


interface FormExtender
{

	public function extend($form): FormExtender;


	public function getForm(): object;


	public function render(): void;


	public function addSelect(InputSpecification $spec): FormExtender;


	public function addCheckboxList(InputSpecification $spec): FormExtender;


	public function addSwitch(InputSpecification $spec): FormExtender;


	public function addText(InputSpecification $spec): FormExtender;


	public function addTextArea(InputSpecification $spec): FormExtender;


	public function addHidden(InputSpecification $spec): FormExtender;


	public function addError(string $message, string $inputName = null): FormExtender;
}
