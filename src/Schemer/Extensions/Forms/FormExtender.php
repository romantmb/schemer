<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Forms;


interface FormExtender
{

	public function form($form): FormExtender;


	public function extend(SchemeForm $with, callable $onAfter = null): FormExtender;


	public function onSubmit(callable $callback): FormExtender;


	public function isSuccess(): bool;


	public function getValues(): array;


	public function render(): void;


	public function getForm(): object;


	public function addSelect(InputSpecification $spec): FormExtender;


	public function addCheckboxList(InputSpecification $spec): FormExtender;


	public function addSwitch(InputSpecification $spec): FormExtender;


	public function addText(InputSpecification $spec): FormExtender;


	public function addTextArea(InputSpecification $spec): FormExtender;


	public function addHidden(InputSpecification $spec): FormExtender;


	public function addError(string $message, string $inputName = null): FormExtender;
}
