<?php

namespace Schemer\Tests;

use Schemer\Extensions\FormExtender;
use Schemer\Extensions\FormInputSpecification;
use Schemer\Extensions\FormsForSchemer;
use Schemer\Extensions\Transformers\SchemePathToInputNameTransformer;
use Schemer\ManyValuesProvider;
use Schemer\Node;
use Schemer\Property;
use Schemer\Scheme;
use Schemer\UserValueProvider;
use Schemer\Validators\Inputs\BooleanInput;
use Schemer\Validators\Inputs\NullableBooleanInput;
use Schemer\Validators\Inputs\NullableTextualInput;
use Schemer\Validators\Inputs\NumericInput;
use Schemer\Validators\Inputs\CustomInput;
use Schemer\Exceptions\InvalidValueException;
use Schemer\Validators\Inputs\TextualInput;
use Schemer\ValueProvider;
use Tracy\Debugger;
use function Schemer\bag;
use function Schemer\property;
use function Schemer\candidates;
use function Schemer\group;


require_once __DIR__ . '/../../vendor/autoload.php';

Debugger::enable();
Debugger::$maxDepth = 6;


/**
 * Custom validator #1
 */
final class PrizeIdentifierValidator extends CustomInput
{
	private static $prizeRankSymbols = [ '1st', '2nd', '3rd' ];

	protected function validator(): bool
	{
		if (is_numeric($this->value)) {
			$this->value = (int) $this->value;
		}

		return (is_int($this->value) && $this->value > 0)
			|| in_array($this->value, self::$prizeRankSymbols)
			|| self::isArrayOfIds($this->value);
	}

	public function getIssue(): ?string
	{
		if (! $this->isValid()) {
			$mismatch = sprintf(
				is_string($this->value) ? "'%s'" : '%s',
				is_array($this->value) ? implode(', ', $this->value) : $this->value
			);
			return sprintf(
				'value %s is not valid. ' .
				"Prize must be defined as integer (ID) or as one of these symbols: '%s'",
				$mismatch,
				implode("', '", self::$prizeRankSymbols)
			);
		}

		return null;
	}

	protected static function isArrayOfIds($array): bool
	{
		return is_array($array)
			&& ! empty($array)
			&& collect($array)->filter(function($value) {
				return ! is_int($value) || $value < 1;
			})->count() === 0;
	}
}


/**
 * Custom validator #2
 */
final class DrawStartDateValidator extends CustomInput
{
	private static $wildcards = [ 'firstDay', 'dayAfter' ];

	protected function validator(): bool
	{
		if (strpos($this->value, '<') !== false) {
			if (! preg_match(sprintf("~<(%s)>~", implode('|', self::$wildcards)), $this->value)) {
				throw new InvalidValueException(sprintf("Allowed draw date wildcards are '<%s>'.", implode(">' and '<", self::$wildcards)));
			}
		}

		try {
			new \DateTime(preg_replace('~<[^>]+>~', (new \DateTime())->format('Y-m-d'), $this->value));

		} catch (\Exception $e) {
			throw new InvalidValueException(sprintf("Date '%s' is not valid.", $this->value));
		}

		return true;
	}
}


final class SimpleFormBuilder implements FormExtender
{
	private $form;

	public function __construct(& $form)
	{
		$this->form = & $form;
	}

	public function addSelect(FormInputSpecification $spec): void
	{
		$this->form .= self::labeled(
			$spec,
			sprintf(
				'<select name="%s"%s>%s</select>',
				$spec->getInputName(),
				$spec->isDisabled() ? ' disabled' : '',
				implode('', collect([ null => '(choose)' ] + $spec->getOptions())->map(function($option, $key) use ($spec) {
					return sprintf(
						'<option value="%s"%s>%s</option>',
						$key,
						$spec->getValue() === $key ? ' selected' : '',
						$option
					);
				})->all())
			)
		);
	}

	public function addSwitch(FormInputSpecification $spec): void
	{
		$this->form .= self::labeled(
			$spec,
			sprintf(
				'<input type="checkbox" name="%s" value="true"%s%s>',
				$spec->getInputName(),
				$spec->getValue() === true ? ' checked' : '',
				$spec->isDisabled() ? ' disabled' : ''
			)
		);
	}

	public function addText(FormInputSpecification $spec): void
	{
		$this->form .= self::labeled(
			$spec,
			sprintf(
				'<input type="text" name="%s" value="%s"%s>',
				$spec->getInputName(),
				is_array($value = $spec->getValue()) ? implode(',', $value) : $value,
				$spec->isDisabled() ? ' disabled' : ''
			)
		);
	}

	public function addCheckboxList(FormInputSpecification $spec): void
	{
		$this->form .=
			implode('', collect($spec->getOptions())->map(function($option, $key) use ($spec) {
				return sprintf(
					'<input type="checkbox" name="%s[]" value="%s"%s%s>%s</input>',
					$spec->getInputName(),
					$key,
					$spec->getValue() === $key ? ' checked' : '',
					$spec->isDisabled() ? ' disabled' : '',
					$option
				);
			})->all());
	}

	public function addHidden(FormInputSpecification $spec): void
	{
		$this->form .= self::labeled(
			$spec,
			sprintf(
				'<input type="hidden" name="%s" value="%s">',
				$spec->getInputName(),
				is_array($value = $spec->getValue()) ? implode(',', $value) : $value
			)
		);
	}

	public function addError(string $message, string $inputName = null)
	{
	}

	private static function labeled(FormInputSpecification $spec, string $input): string
	{
		return sprintf('<label for="%s">%s</label>', $spec->getInputName(), ucfirst($spec->getLabel()))
			. '<br>' . $input . '<br><br>';
	}
}


/**
 * Schemer simple test case
 */
final class SimpleTestCase
{
	public static function run(): void
	{
		if (isset($_POST['schemeId'])) {
			$schemeId = ucfirst($_POST['schemeId']);
			self::handleSchemeUpdate(
				call_user_func(
					[ SimpleTestCase::class, sprintf('defineScheme%s', $schemeId) ],
					call_user_func(
						[ SimpleTestCase::class, sprintf('buildScheme%s', $schemeId) ]
					)
				)
			);
		}

		echo "<h3>Scheme One</h3>";
		self::print(
			$schemeOne = self::defineSchemeOne(
				self::buildSchemeOne()
			)
		);
		self::renderFormsForScheme($schemeOne);

		echo "<h3>Scheme Two</h3>";
		self::print(
			$schemeTwo = self::defineSchemeTwo(
				self::buildSchemeTwo()
			)
		);
		self::renderFormsForScheme($schemeTwo);
	}

	private static function buildSchemeOne(): Node
	{
		return bag(

			// just for purposes of this test
			property('schemeId', 'one'),

			candidates('draws',

				bag(

					property('prizeId', PrizeIdentifierValidator::class)
						->uniqueKey(),

					property('title', NullableTextualInput::class)
						->default('(default title)'),

					property('trigger', [
						'human',
						'robot',
					]),

					property('contentRestrictions',
						property('alcohol', BooleanInput::class)
							->default(false),
						property('tobacco', BooleanInput::class)
							->default(false),
						property('sexual', BooleanInput::class)
							->default(false),
						property('explicit', BooleanInput::class)
							->default(false)
					),

					property('mechanics', [
						'random',
						'nth',
						'jury',
					])
						->default('random')

						->on('nth', group(

							property('nth',

								property('n', NumericInput::class),

								property('in', [
									'hour',
									'day',
									'week',
									'month',
								])
							)
						)),

					property('rounds', new class(CustomInput::class) extends UserValueProvider implements ManyValuesProvider {

						public function getValues(): array
						{
							return array_merge([ 'all' ], range(1, 10 ));
						}

						public function preserveKeys(): bool
						{
							return false;
						}

						public function multipleValues(): bool
						{
							return true;
						}
					}),

					property('interval',

						property('first', DrawStartDateValidator::class),

						property('repeatEvery', [
							'hour',
							'day',
							'week',
							'month',
						])
					)
				)
			)
		);
	}

	private static function buildSchemeTwo(): Node
	{
		return bag(

			// just for purposes of this test
			property('schemeId', 'two'),

			property('competition',

				candidates('steps',

					bag(

						property('type', [
							'buyGoods',
							'uploadPhoto',
							'typeText',
							'pickOne',
							'signIn',
						])
							->uniqueKey()

							->on('buyGoods', group(

								property('specs',
									property('buy', [
										'atLeastOne',
										'all',
									])
								),
								property('title', TextualInput::class),
								property('prompt')
							))

							->on('uploadPhoto', group(

								property('title', TextualInput::class),
								property('titleAfter', TextualInput::class),
								property('prompt')
							))

							->on('typeText', group(

								property('title', TextualInput::class),
								property('titleAfter', TextualInput::class),
								property('prompt',
									property('type', 'text'),
									property('maxLength', NumericInput::class)
								)
							))

							->on('pickOne', group(

								property('title', TextualInput::class),
								property('titleAfter',
									property('onCorrect', TextualInput::class),
									property('onWrong', TextualInput::class)
								),
								property('prompt',
									property('type', 'options'),
									Scheme::options('options', [
										'a' => 'Řím', 'b' => 'New York', 'c' => 'Lima',
									])
								)
							))
					)
				)
			)
		);
	}

	private static function defineSchemeOne(Node $scheme): Node
	{
		$draws = $scheme->get('draws');

		// Draw #1 scheme def

		$draws->pick('prizeId', [ 1, 2, 3 ])
			->set('trigger', 'human')
			//->set('mechanics', 'random')
			->set('interval.first', '<dayAfter> 12:00:00');

		$draws->set('[prizeId=1,2,3].interval.repeatEvery', 'week');

		// Draw #2 scheme def

		$draws->pick('prizeId', '1st')
			->set('trigger', 'robot');

		$draws->set('[prizeId=1st].mechanics', 'nth');

		$drawNo2 = $draws->get('[prizeId=1st]');
		$drawNo2
			->set('nth.n', 100)
			->set('nth.in', 'month');

		return $scheme;
	}

	private static function defineSchemeTwo(Node $scheme): Node
	{
		$scheme->initialize('{"competition":{"steps":[{"type":"buyGoods","specs":{"buy":"atLeastOne"},"title":"Nakup jak\u00fdkoliv produkt Vileda","prompt":null},{"type":"uploadPhoto","title":"Vyfo\u0165 \u00fa\u010dtenku","titleAfter":"Tvoje sout\u011b\u017en\u00ed \u00fa\u010dtenka","prompt":null},{"type":"pickOne","title":"Jak\u00e9 je hlavn\u00ed m\u011bsto Peru?","titleAfter":{"onCorrect":"Super, to je spr\u00e1vn\u011b!","onWrong":"Ou, \u0161patn\u00e1 odpov\u011b\u010f. :-("},"prompt":{"type":"options","options":[{"key":"a","value":"\u0158\u00edm"},{"key":"b","value":"New York"},{"key":"c","value":"Lima"}]}},{"type":"signIn"}]}}');

		return $scheme;
	}

	private static function renderFormsForScheme(Node $scheme): void
	{
		echo $form = sprintf('<form action="%s" method="post">', basename(__FILE__));

		(new FormsForSchemer)
			->fromScheme($scheme)

			// ToDo: Finish form inputs for existing nodes update
			/*->filter(function(FormInputSpecification $spec) {
				return $spec->getGroup() !== null;
			})
			->map(function(FormInputSpecification $spec) {
				if ($spec->getName() === 'prizeId' && $spec->getGroup() !== null) {
					$spec->setAsDisabled();
				}
			})
			->extendForm(new SimpleFormBuilder($form))*/

			->filter(function(FormInputSpecification $spec) {
				return $spec->getGroup() === null;
			})
			->extendForm(new SimpleFormBuilder($form));

		$form .= sprintf(
			'<input type="hidden" name="schemeId" value="%s">' .
				'<input type="submit" name="update" value="Update scheme">',
			$scheme->get('schemeId')->getValue()
		);

		echo $form . '</form>';
	}

	private static function handleSchemeUpdate(Node $scheme): void
	{
		$values = array_filter($_POST, function(& $value, $key) {
			return strpos($key, SchemePathToInputNameTransformer::INPUT_PREFIX) === 0;
		},ARRAY_FILTER_USE_BOTH);

		$values = array_map(function($value) {
			if (in_array($value, [ 'true', 'false' ], true)) {
				$value = $value === 'true';
			}
			return $value;
		}, $values);

		(new FormsForSchemer)
			->fromScheme($scheme)
			->updateScheme($scheme, $values);

		echo sprintf('<h3>Updated scheme %s</h3>', ucfirst($scheme->get('schemeId')->getValue()));

		self::print($scheme);

		exit;
	}

	private static function print(Node $scheme): void
	{
		echo "<pre style=\"border: 1px solid rgba(0,0,0,.1)\"><code class=\"language-json\">" . htmlspecialchars($scheme->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</code></pre>"
			. "<link href=\"../assets/plugins/prismjs/prism.css\" rel=\"stylesheet\" />"
			. "<script src=\"../assets/plugins/prismjs/prism.js\"></script>";
	}
}


SimpleTestCase::run();
