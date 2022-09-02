<?php

/**
 * Schemer
 * @author Roman Pistek
 * @noinspection PhpUnusedPrivateFieldInspection
 */

declare(strict_types=1);

namespace Schemer\Bridges\SchemerNetteForms;

use Schemer\Extensions\Forms\SchemeForm;
use Schemer\Extensions\Forms\FormExtender as Extender;
use Schemer\Extensions\Forms\InputSpecification;
use Schemer\Exceptions\SchemeFormException;
use Nette\Forms\Form;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\BaseControl;
use Closure;
use InvalidArgumentException;
use LogicException;


final class FormExtender implements Extender
{
	private Form $form;

	private bool $extended = false;

	private Closure $_onValidate;

	private Closure $_onSubmit;

	private Closure $_onSuccess;

	private Closure $_onError;


	public function __construct(mixed $form = null)
	{
		if (isset($form)) {
			$this->with($form);
		}
	}


	public function with($form): self
	{
		if (! $form instanceof Form) {
			throw new InvalidArgumentException(sprintf('Argument must be an instance of %s.', Form::class));
		}

		$this->form = $form;
		return $this;
	}


	public function extend(SchemeForm $schemeForm, callable $onAfter = null): self
	{
		if ($this->extended) {
			return $this;
		}

		$schemeForm->collect()->each(fn(InputSpecification $spec) => match (true) {
			$spec->isHidden()      => $this->addHidden($spec),
			$spec->isSelect()      => $this->addSelect($spec),
			$spec->isMultiSelect() => $this->addCheckboxList($spec),
			$spec->isSwitch()      => $this->addSwitch($spec),
			default                => $this->addText($spec),
		});

		$n = static fn($f) => $f;

		($onAfter ?? $n)($this->form());
		$this->extended = true;

		if (! $this->signalDrivenForm() && $this->form()->isSubmitted()) {
			($this->_onValidate ?? $n)($schemeForm);
			($this->_onSubmit ?? $n)($schemeForm);
			$this->isSuccess()
				? ($this->_onSuccess ?? $n)($schemeForm)
				: ($this->_onError ?? $n)($schemeForm);
		}

		return $this;
	}


	public function render(...$args): void
	{
		$this->getForm()->render(...$args);
	}


	public function isSuccess(): bool
	{
		return $this->getForm()->isSuccess();
	}


	public function getValues(): array
	{
		return $this->getForm()->getValues('array');
	}


	public function getErrors(): array
	{
		return $this->getForm()->getErrors();
	}


	public function onValidate(callable $callback): self
	{
		return $this->registerEventHandler(on: 'validate', do: $callback);
	}


	public function onSubmit(callable $callback): self
	{
		return $this->registerEventHandler(on: 'submit', do: $callback);
	}


	public function onSuccess(callable $callback): self
	{
		return $this->registerEventHandler(on: 'success', do: $callback);
	}


	public function onError(callable $callback): self
	{
		return $this->registerEventHandler(on: 'error', do: $callback);
	}


	public function getForm(): Form
	{
		if (! $this->extended) {
			throw new LogicException('Form has not yet been extended.');
		}

		return $this->form();
	}


	public function addSelect(InputSpecification $spec): self
	{
		return $this->addFormControl($spec, 'Select');
	}


	public function addCheckboxList(InputSpecification $spec): self
	{
		return $this->addFormControl($spec, 'CheckboxList');
	}


	public function addSwitch(InputSpecification $spec): self
	{
		return $this->addFormControl($spec, 'Checkbox');
	}


	public function addText(InputSpecification $spec): self
	{
		return $this->addFormControl($spec, 'Text');
	}


	public function addTextArea(InputSpecification $spec): self
	{
		return $this->addFormControl($spec, 'TextArea');
	}


	public function addHidden(InputSpecification $spec): self
	{
		return $this->addFormControl($spec, 'Hidden');
	}


	public function addError(string $message, string $inputName = null): self
	{
		($inputName !== null ? $this->getControl($inputName) : $this->form())
			->addError($message);

		return $this;
	}


	private function addFormControl(InputSpecification $spec, string $type): self
	{
		/** @var BaseControl $control */
		$control = $this->form()->{"add$type"}(...self::args($spec, $type))
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
		return $this->form()->getComponent($name);
	}


	private function registerEventHandler(string $on, callable $do): self
	{
		if ($this->signalDrivenForm()) {
			$this->form()->{'on'.ucfirst($on)}[] = $do;
			return $this;
		}

		$this->{'_on'.ucfirst($on)} = Closure::fromCallable($do);
		return $this;
	}


	private function signalDrivenForm(): bool
	{
		return is_a($this->form(), 'Nette\Application\UI\SignalReceiver');
	}


	private function form(): Form
	{
		return $this->form ?? throw new SchemeFormException('Undefined form.');
	}
}
