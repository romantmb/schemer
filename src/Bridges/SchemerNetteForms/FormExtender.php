<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Bridges\SchemerNetteForms;

use Closure;
use Schemer\Extensions\Forms\SchemeForm;
use Schemer\Extensions\Forms\FormExtender as Extender;
use Schemer\Extensions\Forms\InputSpecification;
use Nette\Forms\Form;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\BaseControl;
use InvalidArgumentException;
use LogicException;


final class FormExtender implements Extender
{
	private Form $form;

	private bool $extended = false;

	private Closure $_onSubmit;


	public function form($form): FormExtender
	{
		if (! $form instanceof Form) {
			throw new InvalidArgumentException(sprintf('Argument must be an instance of %s.', Form::class));
		}

		$this->form = $form;
		return $this;
	}


	public function extend(SchemeForm $with, callable $onAfter = null): FormExtender
	{
		if (! $this->extended) {
			$with->collect()->each(fn(InputSpecification $spec) => match (true) {
				$spec->isHidden()      => $this->addHidden($spec),
				$spec->isSelect()      => $this->addSelect($spec),
				$spec->isMultiSelect() => $this->addCheckboxList($spec),
				$spec->isSwitch()      => $this->addSwitch($spec),
				default                => $this->addText($spec),
			});

			($onAfter ?? static fn($f) => $f)($this->form);
			$this->extended = true;

			if (isset($this->_onSubmit) && $this->isSuccess()) {
				($this->_onSubmit)($with);
			}
		}

		return $this;
	}


	public function onSubmit(callable $callback): FormExtender
	{
		if (is_a($this->form, 'Nette\Application\UI\SignalReceiver')) {
			$this->form->onSubmit[] = $callback;

		} else {
			$this->_onSubmit = Closure::fromCallable($callback);
		}

		return $this;
	}


	public function isSuccess(): bool
	{
		return $this->getForm()->isSuccess();
	}


	public function getValues(): array
	{
		return $this->getForm()->getValues('array');
	}


	public function render(...$args): void
	{
		$this->getForm()->render(...$args);
	}


	public function getForm(): Form
	{
		if (! $this->extended) {
			throw new LogicException('Form has not yet been extended.');
		}

		return $this->form;
	}


	public function addSelect(InputSpecification $spec): FormExtender
	{
		return $this->addFormControl($spec, 'Select');
	}


	public function addCheckboxList(InputSpecification $spec): FormExtender
	{
		return $this->addFormControl($spec, 'CheckboxList');
	}


	public function addSwitch(InputSpecification $spec): FormExtender
	{
		return $this->addFormControl($spec, 'Checkbox');
	}


	public function addText(InputSpecification $spec): FormExtender
	{
		return $this->addFormControl($spec, 'Text');
	}


	public function addTextArea(InputSpecification $spec): FormExtender
	{
		return $this->addFormControl($spec, 'TextArea');
	}


	public function addHidden(InputSpecification $spec): FormExtender
	{
		return $this->addFormControl($spec, 'Hidden');
	}


	public function addError(string $message, string $inputName = null): FormExtender
	{
		($inputName !== null ? $this->getControl($inputName) : $this->form)
			->addError($message);

		return $this;
	}


	private function addFormControl(InputSpecification $spec, string $type): self
	{
		/** @var BaseControl $control */
		$control = $this->form->{"add$type"}(...self::args($spec, $type))
			->setDefaultValue($spec->getValue());

		if ($type !== 'Hidden') {
			$control
				->setRequired($spec->isRequired())
				->setDisabled($spec->isDisabled());
		}

		if ($type === 'Select') {
			/** @var SelectBox $control */
			$control->setPrompt('(choose one)');
		}

		if (in_array($type, [ 'Select', 'Switch' ])) {
			$control->setHtmlAttribute('data-has-conditional-siblings', $spec->hasAnyConditionalSiblings());
		}

		$spec->setFormControl($control);

		return $this;
	}


	private static function args(InputSpecification $spec, string $type): array
	{
		$args = [ $spec->getInputName() ];

		if ($type !== 'Hidden') {
			$args[] = $spec->getLabel();
		}

		if (in_array($type, [ 'Select', 'CheckboxList' ])) {
			$args[] = $spec->getOptions();
		}

		return $args;
	}


	/**
	 * @noinspection PhpIncompatibleReturnTypeInspection
	 */
	private function getControl(string $name): BaseControl
	{
		return $this->form->getComponent($name);
	}
}
