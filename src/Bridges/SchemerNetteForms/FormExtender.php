<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Bridges\SchemerNetteForms;

use Schemer\Extensions\Forms\FormExtender as Extender;
use Schemer\Extensions\Forms\InputSpecification;
use Nette\Forms\Form;
use Nette\Forms\Controls\BaseControl;
use InvalidArgumentException;


final class FormExtender implements Extender
{
	private Form $form;


	public function extend($form): FormExtender
	{
		if (! $form instanceof Form) {
			throw new InvalidArgumentException(sprintf('Argument must be an instance of %s.', Form::class));
		}

		$this->form = $form;
		return $this;
	}


	public function getForm(): Form
	{
		return $this->form;
	}


	public function render(...$args): void
	{
		$this->form->render(...$args);
	}


	public function addSelect(InputSpecification $spec): FormExtender
	{
		$spec->setFormControl(
			$this->form->addSelect($spec->getInputName(), $spec->getLabel(), $spec->getOptions())
				->setPrompt('(choose one)')
				->setRequired($spec->isRequired())
				->setDisabled($spec->isDisabled())
				->setDefaultValue($spec->getValue())
				->setHtmlAttribute('data-has-conditional-siblings', $spec->hasAnyConditionalSiblings())
		);

		return $this;
	}


	public function addCheckboxList(InputSpecification $spec): FormExtender
	{
		$spec->setFormControl(
			$this->form->addCheckboxList($spec->getInputName(), $spec->getLabel(), $spec->getOptions())
				->setRequired($spec->isRequired())
				->setDisabled($spec->isDisabled())
				->setDefaultValue($spec->getValue())
		);

		return $this;
	}


	public function addSwitch(InputSpecification $spec): FormExtender
	{
		$spec->setFormControl(
			$this->form->addCheckbox($spec->getInputName(), $spec->getLabel())
				->setRequired($spec->isRequired())
				->setDisabled($spec->isDisabled())
				->setDefaultValue($spec->getValue())
				->setHtmlAttribute('data-has-conditional-siblings', $spec->hasAnyConditionalSiblings())
		);

		return $this;
	}


	public function addText(InputSpecification $spec): FormExtender
	{
		$spec->setFormControl(
			$this->form->addText($spec->getInputName(), $spec->getLabel())
				->setRequired($spec->isRequired())
				->setDisabled($spec->isDisabled())
				->setDefaultValue($spec->getValue())
		);

		return $this;
	}


	public function addTextArea(InputSpecification $spec): FormExtender
	{
		$spec->setFormControl(
			$this->form->addTextArea($spec->getInputName(), $spec->getLabel())
				->setRequired($spec->isRequired())
				->setDisabled($spec->isDisabled())
				->setDefaultValue($spec->getValue())
		);

		return $this;
	}


	public function addHidden(InputSpecification $spec): FormExtender
	{
		$spec->setFormControl(
			$this->form->addHidden($spec->getInputName())
				->setDefaultValue($spec->getValue())
		);

		return $this;
	}


	public function addError(string $message, string $inputName = null): FormExtender
	{
		($inputName !== null ? $this->getControl($inputName) : $this->form)
			->addError($message);

		return $this;
	}


	/**
	 * @noinspection PhpIncompatibleReturnTypeInspection
	 */
	private function getControl(string $name): BaseControl
	{
		return $this->form->getComponent($name);
	}
}
