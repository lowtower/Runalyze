<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form;

use DB;
use Runalyze\Bundle\CoreBundle\Component\Search\SearchResults;
use Runalyze\Bundle\CoreBundle\Form\Type\SearchConditionValueType;
use Runalyze\Configuration;
use Runalyze\Data\Weather\Condition;
use Runalyze\Data\Weather\Humidity;
use Runalyze\Data\Weather\Pressure;
use Runalyze\Data\Weather\WindSpeed;
use Runalyze\Dataset\Keys;
use SessionAccountHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Search window form, replacing the legacy SearchFormular. Field names are
 * identical to the legacy form (flat names, conditions as opt[name] +
 * name), so that the search results query (which reads the superglobals)
 * and the pagination JS keep working unchanged.
 *
 * Must only be used after the legacy environment has been bootstrapped
 * (i.e. after constructing a Frontend instance).
 */
class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $numericOperators = [
            '=' => 'eq',
            '>' => 'gt',
            '≥' => 'ge',
            '≤' => 'le',
            '<' => 'lt',
            '≠' => 'ne',
        ];
        $stringOperators = [
            '=' => 'eq',
            '≠' => 'ne',
            '≈' => 'like',
        ];

        $builder
            ->add('opt', FormType::class)
            ->add('page', HiddenType::class, [
                'data' => '1',
            ])
            ->add('sportid', ChoiceType::class, $this->idChoiceOptions($this->dbOptions('sport', 'name'), [
                'label' => __('Sport') . $this->sportShortLinks(),
                'multiple' => true,
                'attr' => [
                    'class' => 'chosen-select full-size',
                    'data-placeholder' => __('Choose sport(s)'),
                ],
            ]))
            ->add('date-from', SearchConditionValueType::class, [
                'label' => __('Time range'),
                'translation_domain' => false,
                'required' => false,
                'unit' => '<i class="fa far fa-fw fa-calendar"></i>',
                'attr' => [
                    'class' => 'pick-a-date small-size',
                ],
            ])
            ->add('date-to', SearchConditionValueType::class, [
                'label' => false,
                'translation_domain' => false,
                'required' => false,
                'unit' => '<i class="fa far fa-fw fa-calendar"></i>',
                'attr' => [
                    'class' => 'pick-a-date small-size',
                ],
            ])
            ->add('search-sort-by', ChoiceType::class, $this->idChoiceOptions($this->sortByOptions(), [
                'label' => __('Sorting'),
            ]))
            ->add('search-sort-order', ChoiceType::class, $this->valueChoiceOptions([
                __('descending') => 'DESC',
                __('ascending') => 'ASC',
            ]))
            ->add('typeid', ChoiceType::class, $this->idChoiceOptions($this->dbOptions('type', 'name'), [
                'label' => __('Type'),
                'multiple' => true,
                'attr' => [
                    'class' => 'chosen-select full-size',
                    'data-placeholder' => __('Choose activity type(s)'),
                ],
            ]))
            ->add('weatherid', ChoiceType::class, $this->idChoiceOptions($this->weatherOptions(), [
                'label' => __('Weather'),
                'multiple' => true,
                'attr' => [
                    'class' => 'chosen-select full-size',
                    'data-placeholder' => __('Choose weather conditions'),
                ],
            ]))
            ->add('equipmentid', ChoiceType::class, $this->idChoiceOptions($this->dbOptions('equipment', 'name'), [
                'label' => __('Equipment'),
                'multiple' => true,
                'attr' => [
                    'class' => 'chosen-select full-size',
                    'data-placeholder' => __('Choose equipment'),
                ],
            ]))
            ->add('tagid', ChoiceType::class, $this->idChoiceOptions($this->dbOptions('tag', 'tag'), [
                'label' => __('Tag'),
                'multiple' => true,
                'attr' => [
                    'class' => 'chosen-select full-size',
                    'data-placeholder' => __('Choose tag'),
                ],
            ]))
            ->add('notes', TextType::class, [
                'label' => __('Notes'),
                'translation_domain' => false,
                'required' => false,
                'attr' => [
                    'class' => 'full-size',
                ],
            ]);

        $builder->get('opt')
            ->add('notes', HiddenType::class);

        $this->addCondition($builder, 'distance', __('Distance'), Configuration::General()->distanceUnitSystem()->distanceUnit(), $numericOperators);
        $this->addCondition($builder, 'elevation', __('Elevation'), Configuration::General()->distanceUnitSystem()->elevationUnit(), $numericOperators);
        $this->addCondition($builder, 'route', __('Route'), '', $stringOperators, 'middle-size');
        $this->addCondition($builder, 's', __('Duration'), '', $numericOperators, 'small-size', 'h:mm:ss');
        $this->addCondition($builder, 'gradient', __('Gradient'), '%', $numericOperators);
        $this->addCondition($builder, 'title', __('Title'), '', $stringOperators, 'middle-size');
        $this->addCondition($builder, 'pace', __('Pace'), '/km', $numericOperators);
        $this->addCondition($builder, 'cadence', __('Cadence'), 'spm', $numericOperators);
        $this->addCondition($builder, 'partner', __('Partner'), '', $stringOperators, 'middle-size');
        $this->addCondition($builder, 'pulse_avg', __('avg. HR'), 'bpm', $numericOperators);
        $this->addCondition($builder, 'stride_length', __('Stride length'), Configuration::General()->distanceUnitSystem()->strideLengthUnit(), $numericOperators);
        $this->addCondition($builder, 'pulse_max', __('max. HR'), 'bpm', $numericOperators);
        $this->addCondition($builder, 'power', __('Power'), 'W', $numericOperators);
        $this->addCondition($builder, 'pulse_avg_active', __('avg. HR') . ' active', 'bpm', $numericOperators);
        $this->addCondition(
            $builder,
            Configuration::VO2max()->useElevationCorrection() ? 'vo2max_with_elevation' : 'vo2max',
            'VO2max',
            '',
            $numericOperators
        );
        $this->addCondition($builder, 'vertical_oscillation', __('Vertical oscillation'), 'cm', $numericOperators);
        $this->addCondition($builder, 'trimp', __('TRIMP'), '', $numericOperators);
        $this->addCondition($builder, 'vertical_ratio', __('Vertical ratio'), '%', $numericOperators);
        $this->addCondition($builder, 'rpe', __('RPE'), '', $numericOperators);
        $this->addCondition($builder, 'groundcontact', __('Ground contact'), 'ms', $numericOperators);
        $this->addCondition($builder, 'temperature', __('Temperature'), Configuration::General()->temperatureUnit()->unit(), $numericOperators);
        $this->addCondition($builder, 'kcal', __('Energy'), Configuration::General()->energyUnit()->unit(), $numericOperators);
        $this->addCondition($builder, 'groundcontact_balance', __('Ground Contact Balance'), 'L%', $numericOperators);
        $this->addCondition($builder, 'wind_speed', __('Wind Speed'), new WindSpeed()->unit(), $numericOperators);
        $this->addCondition($builder, 'fit_performance_condition', __('Performance condition'), '', $numericOperators);
        $this->addCondition($builder, 'fit_training_effect', __('(Aerob) Training Effect'), '', $numericOperators);
        $this->addCondition($builder, 'humidity', __('Humidity'), new Humidity()->unit(), $numericOperators);
        $this->addCondition($builder, 'fit_vo2max_estimate', 'VO2max (laut Datei)', '', $numericOperators);
        $this->addCondition($builder, 'fit_recovery_time', Keys::get(Keys::FIT_RECOVERY_TIME)->label(), 'h', $numericOperators);
        $this->addCondition($builder, 'pressure', __('Pressure'), new Pressure()->unit(), $numericOperators);
        $this->addCondition($builder, 'climb_score', __('Climb Score'), '', $numericOperators);
        $this->addCondition($builder, 'percentage_hilly', __('Percentage Hilly'), '%', $numericOperators);
        $this->addCondition($builder, 'avg_respiration_rate', 'Respiration rate', 'brpm', $numericOperators);

        $booleanChoices = [
            '' => '',
            __('Yes') => '1',
            __('No') => '0',
        ];

        $builder
            ->add('is_public', ChoiceType::class, $this->valueChoiceOptions($booleanChoices, [
                'label' => __('Is public'),
            ]))
            ->add('is_race', ChoiceType::class, $this->valueChoiceOptions($booleanChoices, [
                'label' => __('Is race'),
            ]))
            ->add('use_vo2max', ChoiceType::class, $this->valueChoiceOptions($booleanChoices, [
                'label' => __('Uses VO2max'),
            ]))
            ->add('is_track', ChoiceType::class, $this->valueChoiceOptions($booleanChoices, [
                'label' => __('Track'),
            ]))
            ->add('send-to-multi-editor', CheckboxType::class, [
                'label' => '<span class="atRight" rel="tooltip" title="'
                    . sprintf(__('Our multi editor is limited to a maximum of %d activities.'), SearchResults::MAX_LIMIT_FOR_MULTI_EDITOR)
                    . '">' . __('Send to multi editor') . '</span>',
                'label_attr' => [
                    'class' => 'checkable',
                ],
                'translation_domain' => false,
                'required' => false,
            ])
            ->add('resultsPerPage', ChoiceType::class, $this->valueChoiceOptions([
                '10' => '10',
                '20' => '20',
                '50' => '50',
                '100' => '100',
                '200' => '200',
            ], [
                'label' => __('Results per page'),
            ]))
            ->add('submit', SubmitType::class, [
                'label' => __('Search'),
                'translation_domain' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * Operator fallback values used to preselect the operator selects on a
     * fresh form, keyed like the posted opt array.
     *
     * @return array<string, string>
     */
    public static function defaultOperators(): array
    {
        return [
            'distance' => 'eq',
            'elevation' => 'eq',
            'route' => 'eq',
            's' => 'gt',
            'gradient' => 'eq',
            'title' => 'eq',
            'pace' => 'eq',
            'cadence' => 'eq',
            'partner' => 'eq',
            'pulse_avg' => 'eq',
            'stride_length' => 'eq',
            'pulse_max' => 'eq',
            'power' => 'eq',
            'pulse_avg_active' => 'eq',
            'vo2max' => 'eq',
            'vo2max_with_elevation' => 'eq',
            'vertical_oscillation' => 'eq',
            'trimp' => 'eq',
            'vertical_ratio' => 'eq',
            'rpe' => 'eq',
            'groundcontact' => 'eq',
            'temperature' => 'eq',
            'kcal' => 'eq',
            'groundcontact_balance' => 'eq',
            'wind_speed' => 'eq',
            'fit_performance_condition' => 'eq',
            'fit_training_effect' => 'eq',
            'humidity' => 'eq',
            'fit_vo2max_estimate' => 'eq',
            'fit_recovery_time' => 'eq',
            'pressure' => 'eq',
            'climb_score' => 'eq',
            'percentage_hilly' => 'eq',
            'avg_respiration_rate' => 'eq',
            'notes' => 'like',
        ];
    }

    /**
     * @param array<string, string> $operatorChoices
     */
    private function addCondition(FormBuilderInterface $builder, string $name, string $label, string $unit, array $operatorChoices, string $size = 'small-size', ?string $placeholder = null): void
    {
        $builder->add($name, SearchConditionValueType::class, [
            'label' => $label,
            'translation_domain' => false,
            'required' => false,
            'unit' => $unit,
            'attr' => $placeholder === null ? [
                'class' => $size,
            ] : [
                'class' => $size,
                'placeholder' => $placeholder,
            ],
        ]);

        $builder->get('opt')
            ->add($name, ChoiceType::class, $this->valueChoiceOptions($operatorChoices, [
                'label' => false,
            ]));
    }

    /**
     * Choice options for an id => label map, keeping ids as choice values.
     *
     * @param array<string|int, string> $idToLabel
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function idChoiceOptions(array $idToLabel, array $overrides = []): array
    {
        return array_merge([
            'choices' => array_keys($idToLabel),
            'choice_value' => fn (int|string|null $choice = null): string => $choice === null ? '' : (string) $choice,
            'choice_label' => fn (int|string $choice): string => $idToLabel[$choice] ?? (string) $choice,
            'choice_translation_domain' => false,
            'translation_domain' => false,
            'required' => false,
            'placeholder' => false, // do not add an automatic empty option (legacy form has none either)
        ], $overrides);
    }

    /**
     * Choice options for a label => value map.
     *
     * @param array<string, string> $labelToValue
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function valueChoiceOptions(array $labelToValue, array $overrides = []): array
    {
        return array_merge([
            'choices' => $labelToValue,
            'choice_translation_domain' => false,
            'translation_domain' => false,
            'required' => false,
            'placeholder' => false, // do not add an automatic empty option (booleans have an explicit empty choice)
        ], $overrides);
    }

    /**
     * @return array<string, string>
     */
    private function weatherOptions(): array
    {
        $options = [];
        $condition = new Condition(0);

        foreach (Condition::completeList() as $id) {
            $condition->set($id);

            $options[(string) $id] = $condition->string();
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function dbOptions(string $table, string $key): array
    {
        $options = [];

        $result = DB::getInstance()->query(
            'SELECT `id`, `' . $key . '` as `value` '
            . 'FROM `' . PREFIX . $table . '` '
            . 'WHERE `accountid`=' . SessionAccountHandler::getId() . ' '
            . 'ORDER BY `id` ASC'
        )->fetchAll();

        foreach ($result as $option) {
            $options[(string) $option['id']] = (string) $option['value'];
        }

        return $options;
    }

    private function sportShortLinks(): string
    {
        $code = '<span class="link chosen-select-all" data-target="sportid">' . __('all') . '</span>';
        $code .= ' | ';
        $code .= '<span class="link chosen-select-none" data-target="sportid">' . __('none') . '</span>';

        return '<span class="right small">' . $code . '&nbsp;</span>';
    }

    /**
     * @return array<string, string>
     */
    private function sortByOptions(): array
    {
        $options = [];

        foreach ($this->sortByOptionIds() as $keyIdOrArray) {
            if (is_array($keyIdOrArray)) {
                $options[$keyIdOrArray[0]] = $keyIdOrArray[1];
            } else {
                $key = Keys::get($keyIdOrArray);
                $column = $key->column();

                if (! is_array($column)) {
                    $options[$column] = $key->label();
                }
            }
        }

        return $options;
    }

    /**
     * @return list<array{0: string, 1: string}|int>
     */
    private function sortByOptionIds(): array
    {
        return [
            ['time', __('Date')],
            Keys::DISTANCE,
            Keys::DURATION,
            ['pace', __('Pace')],
            Keys::ELAPSED_TIME,
            Keys::ELEVATION,
            ['gradient', __('Gradient')],
            Keys::CLIMB_SCORE,
            Keys::PERCENTAGE_HILLY,
            Keys::HEARTRATE_AVG,
            Keys::HEARTRATE_MAX,
            Keys::TRIMP,
            Keys::RPE,
            Keys::ENERGY,
            Keys::VO2MAX_VALUE,
            Keys::POWER,
            Keys::CADENCE,
            Keys::STRIDE_LENGTH,
            Keys::GROUNDCONTACT,
            Keys::GROUNDCONTACT_BALANCE,
            Keys::VERTICAL_OSCILLATION,
            Keys::VERTICAL_RATIO,
            ['flight_time', __('Flight time')],
            ['flight_ratio', __('Flight ratio')],
            ['avg_impact_gs_left', __('Impact Gs') . ' (' . __('left') . ')'],
            ['avg_impact_gs_right', __('Impact Gs') . ' (' . __('right') . ')'],
            ['avg_braking_gs_left', __('Braking Gs') . ' (' . __('left') . ')'],
            ['avg_braking_gs_right', __('Braking Gs') . ' (' . __('right') . ')'],
            ['avg_footstrike_type_left', __('Footstrike type') . ' (' . __('left') . ')'],
            ['avg_footstrike_type_right', __('Footstrike type') . ' (' . __('right') . ')'],
            ['avg_pronation_excursion_left', __('Pronation excursion') . ' (' . __('left') . ')'],
            ['avg_pronation_excursion_right', __('Pronation excursion') . ' (' . __('right') . ')'],
            Keys::TOTAL_STROKES,
            Keys::SWOLF,
            Keys::FIT_VO2MAX_ESTIMATE,
            Keys::FIT_RECOVERY_TIME,
            Keys::FIT_HRV_ANALYSIS,
            Keys::FIT_TRAINING_EFFECT,
            Keys::FIT_PERFORMANCE_CONDITION_START,
            Keys::FIT_PERFORMANCE_CONDITION_END,
            Keys::TEMPERATURE,
            ['wind_speed', __('Wind speed')],
            ['wind_deg', __('Wind direction')],
            Keys::HUMIDITY,
            Keys::AIR_PRESSURE,
        ];
    }
}
