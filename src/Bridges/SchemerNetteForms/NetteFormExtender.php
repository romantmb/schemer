<?php

/**
 * Schemer
 * @author Roman Pistek
 */

namespace Schemer\Bridges\SchemerNetteForms;

use Schemer\Extensions\FormExtender;
use Schemer\Extensions\FormInputSpecification;
use Nette\Forms\Form;
use Nette\Forms\Controls\BaseControl;


final class NetteFormExtender implements FormExtender
{
	/** @var Form */
	private $form;


	/**
	 * @param Form $form
	 */
	public function __construct(Form $form)
	{
		$this->form = $form;
	}


	/**
	 * @param FormInputSpecification $spec
	 */
	public function addSelect(FormInputSpecification $spec)
	{
		$control = $this->form->addSelect($spec->getInputName(), $spec->getLabel(), $spec->getOptions())
			->setPrompt('(choose one)')
			->setRequired($spec->isRequired())
			->setDisabled($spec->isDisabled())
			->setDefaultValue($spec->getValue());

		$spec->setFormControl($control);
	}


	/**
	 * @param FormInputSpecification $spec
	 */
	public function addCheckboxList(FormInputSpecification $spec)
	{
		$control = $this->form->addCheckboxList($spec->getInputName(), $spec->getLabel(), $spec->getOptions())
			->setRequired($spec->isRequired())
			->setDisabled($spec->isDisabled())
			->setDefaultValue($spec->getValue());

		$spec->setFormControl($control);
	}


	/**
	 * @param FormInputSpecification $spec
	 */
	public function addSwitch(FormInputSpecification $spec)
	{
		$control = $this->form->addCheckbox($spec->getInputName(), $spec->getLabel())
			->setRequired($spec->isRequired())
			->setDisabled($spec->isDisabled())
			->setDefaultValue($spec->getValue());

		$spec->setFormControl($control);
	}


	/**
	 * @param FormInputSpecification $spec
	 */
	public function addText(FormInputSpecification $spec)
	{
		$control = $this->form->addText($spec->getInputName(), $spec->getLabel())
			->setRequired($spec->isRequired())
			->setDisabled($spec->isDisabled())
			->setDefaultValue($spec->getValue());

		$spec->setFormControl($control);
	}


	/**
	 * @param string      $message
	 * @param string|null $inputName
	 */
	public function addError(string $message, string $inputName = null)
	{
		if ($inputName !== null) {
			$this->getControl($inputName)->addError($message);

		} else {
			$this->form->addError($message);
		}
	}


	/**
	 * @param string $name
	 * @return BaseControl
	 */
	private function getControl(string $name): BaseControl
	{
		/** @var BaseControl $control */
		$control = $this->form->getComponent($name, BaseControl::class);

		return $control;
	}
}
