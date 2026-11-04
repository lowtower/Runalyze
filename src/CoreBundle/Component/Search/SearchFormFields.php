<?php

namespace Runalyze\Bundle\CoreBundle\Component\Search;

use DB;
use Runalyze\Activity\Duration;
use Runalyze\Configuration;
use Runalyze\Data\Weather\Condition;
use Runalyze\Data\Weather\Humidity;
use Runalyze\Data\Weather\Pressure;
use Runalyze\Data\Weather\WindSpeed;
use Runalyze\Dataset\Keys;
use SearchResults;
use SessionAccountHandler;

/**
 * Builds the field definitions for the activity search form as a plain array
 * to be consumed by templates/my/search.html.twig, replacing the legacy
 * SearchFormular. Must only be used after the legacy environment has been
 * bootstrapped (i.e. after constructing a Frontend instance).
 */
class SearchFormFields
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fields(): array
    {
        $fields = [];

        $fields[] = $this->sportField();
        $fields[] = $this->timeRangeField();
        $fields[] = $this->sortField();
        $fields[] = $this->chosenField('typeid', 'type', 'name', __('Type'), __('Choose activity type(s)'));
        $fields[] = $this->weatherField();
        $fields[] = $this->chosenField('equipmentid', 'equipment', 'name', __('Equipment'), __('Choose equipment'));
        $fields[] = $this->chosenField('tagid', 'tag', 'tag', __('Tag'), __('Choose tag'));
        $fields[] = $this->notesField();

        // Currently there's a fixed 3-column layout
        // We try to order the fields column-wise, not row-wise
        $fields[] = $this->numericConditionField('distance', __('Distance'), Configuration::General()->distanceUnitSystem()->distanceUnit());
        $fields[] = $this->numericConditionField('elevation', __('Elevation'), Configuration::General()->distanceUnitSystem()->elevationUnit());
        $fields[] = $this->stringConditionField('route', __('Route'));

        $fields[] = $this->durationConditionField('s', __('Duration'));
        $fields[] = $this->numericConditionField('gradient', __('Gradient'), '%');
        $fields[] = $this->stringConditionField('title', __('Title'));

        $fields[] = $this->numericConditionField('pace', __('Pace'), '/km');
        $fields[] = $this->numericConditionField('cadence', __('Cadence'), 'spm');
        $fields[] = $this->stringConditionField('partner', __('Partner'));

        $fields[] = $this->numericConditionField('pulse_avg', __('avg. HR'), 'bpm');
        $fields[] = $this->numericConditionField('stride_length', __('Stride length'), Configuration::General()->distanceUnitSystem()->strideLengthUnit());
        $fields[] = $this->booleanField('is_public', __('Is public'));

        $fields[] = $this->numericConditionField('pulse_max', __('max. HR'), 'bpm');
        $fields[] = $this->numericConditionField('power', __('Power'), 'W');
        $fields[] = $this->booleanField('is_race', __('Is race'));

        $fields[] = $this->numericConditionField('pulse_avg_active', __('avg. HR') . ' active', 'bpm');
        $fields[] = $this->numericConditionField(
            Configuration::VO2max()->useElevationCorrection() ? 'vo2max_with_elevation' : 'vo2max',
            'VO2max'
        );
        $fields[] = $this->numericConditionField('vertical_oscillation', __('Vertical oscillation'), 'cm');

        $fields[] = $this->booleanField('use_vo2max', __('Uses VO2max'));
        $fields[] = $this->numericConditionField('trimp', __('TRIMP'));
        $fields[] = $this->numericConditionField('vertical_ratio', __('Vertical ratio'), '%');

        $fields[] = $this->booleanField('is_track', __('Track'));
        $fields[] = $this->numericConditionField('rpe', __('RPE'));
        $fields[] = $this->numericConditionField('groundcontact', __('Ground contact'), 'ms');

        $fields[] = $this->numericConditionField('temperature', __('Temperature'), Configuration::General()->temperatureUnit()->unit());
        $fields[] = $this->numericConditionField('kcal', __('Energy'), Configuration::General()->energyUnit()->unit());
        $fields[] = $this->numericConditionField('groundcontact_balance', __('Ground Contact Balance'), 'L%');

        $fields[] = $this->numericConditionField('wind_speed', __('Wind Speed'), (new WindSpeed())->unit());
        $fields[] = $this->numericConditionField('fit_performance_condition', __('Performance condition'));
        $fields[] = $this->numericConditionField('fit_training_effect', __('(Aerob) Training Effect'));

        $fields[] = $this->numericConditionField('humidity', __('Humidity'), (new Humidity())->unit());
        $fields[] = $this->numericConditionField('fit_vo2max_estimate', 'VO2max (laut Datei)');
        $fields[] = $this->numericConditionField('fit_recovery_time', Keys::get(Keys::FIT_RECOVERY_TIME)->label(), 'h');

        $fields[] = $this->numericConditionField('pressure', __('Pressure'), (new Pressure())->unit());
        $fields[] = $this->numericConditionField('climb_score', __('Climb Score'));
        $fields[] = $this->numericConditionField('percentage_hilly', __('Percentage Hilly'), '%');

        $fields[] = $this->numericConditionField('avg_respiration_rate', 'Respiration rate', 'brpm');

        $fields[] = $this->multiEditorField();
        $fields[] = $this->resultsPerPageField();
        $fields[] = $this->submitField();

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    private function sportField(): array
    {
        return [
            'kind' => 'multiselect',
            'name' => 'sportid',
            'label' => __('Sport') . $this->shortLinksForSportField(),
            'layout' => 'w100 with33erLabel',
            'options' => $this->dbOptions('sport', 'name'),
            'selected' => $this->postArray('sportid'),
            'placeholder' => __('Choose sport(s)'),
        ];
    }

    private function shortLinksForSportField(): string
    {
        $code = '<span class="link chosen-select-all" data-target="sportid">' . __('all') . '</span>';
        $code .= ' | ';
        $code .= '<span class="link chosen-select-none" data-target="sportid">' . __('none') . '</span>';

        return '<span class="right small">' . $code . '&nbsp;</span>';
    }

    /**
     * @return array<string, mixed>
     */
    private function weatherField(): array
    {
        $options = [];
        $condition = new Condition(0);

        foreach (Condition::completeList() as $id) {
            $condition->set($id);

            $options[(string) $id] = $condition->string();
        }

        return [
            'kind' => 'multiselect',
            'name' => 'weatherid',
            'label' => __('Weather'),
            'layout' => 'w50 with33erLabel',
            'options' => $options,
            'selected' => $this->postArray('weatherid'),
            'placeholder' => __('Choose weather conditions'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function chosenField(string $name, string $table, string $key, string $label, string $placeholder): array
    {
        return [
            'kind' => 'multiselect',
            'name' => $name,
            'label' => $label,
            'layout' => 'w50 with33erLabel',
            'options' => $this->dbOptions($table, $key),
            'selected' => $this->postArray($name),
            'placeholder' => $placeholder,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function dbOptions(string $table, string $key): array
    {
        $options = [];

        $result = DB::getInstance()->query(
            'SELECT \`id\`, \`' . $key . '\` as \`value\` '
            . 'FROM \`' . PREFIX . $table . '\` '
            . 'WHERE \`accountid\`=' . SessionAccountHandler::getId() . ' '
            . 'ORDER BY \`id\` ASC'
        )->fetchAll();

        foreach ($result as $option) {
            $options[(string) $option['id']] = (string) $option['value'];
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private function timeRangeField(): array
    {
        return [
            'kind' => 'time_range',
            'label' => __('Time range'),
            'layout' => 'w50 with33erLabel',
            'from' => $this->postString('date-from') ?? '',
            'to' => $this->postString('date-to') ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sortField(): array
    {
        return [
            'kind' => 'sort',
            'label' => __('Sorting'),
            'layout' => 'w50 with33erLabel',
            'byOptions' => $this->sortByOptions(),
            'bySelected' => $this->postString('search-sort-by'),
            'orderOptions' => [
                'DESC' => __('descending'),
                'ASC' => __('ascending'),
            ],
            'orderSelected' => $this->postString('search-sort-order'),
        ];
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

    /**
     * @return array<string, mixed>
     */
    private function notesField(): array
    {
        return [
            'kind' => 'input',
            'name' => 'notes',
            'label' => __('Notes'),
            'layout' => 'w100 with33erLabel',
            'size' => 'full-size',
            'value' => $this->postString('notes') ?? '',
            'hiddenOperator' => 'like',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function durationConditionField(string $name, string $label): array
    {
        $value = $this->postString($name) ?? '';

        return [
            'kind' => 'condition',
            'name' => $name,
            'label' => $label,
            'layout' => 'w33',
            'size' => 'small-size',
            'unit' => '',
            'value' => ($value == 0) ? '' : Duration::format($value),
            'operators' => $this->numericOperators(),
            'operator' => $this->postOperator($name),
            'placeholder' => 'h:mm:ss',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function numericConditionField(string $name, string $label, string $unit = ''): array
    {
        return [
            'kind' => 'condition',
            'name' => $name,
            'label' => $label,
            'layout' => 'w33',
            'size' => 'small-size',
            'unit' => $unit,
            'value' => $this->postString($name) ?? '',
            'operators' => $this->numericOperators(),
            'operator' => $this->postOperator($name),
            'placeholder' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stringConditionField(string $name, string $label): array
    {
        return [
            'kind' => 'condition',
            'name' => $name,
            'label' => $label,
            'layout' => 'w33',
            'size' => 'middle-size',
            'unit' => '',
            'value' => $this->postString($name) ?? '',
            'operators' => $this->stringOperators(),
            'operator' => $this->postOperator($name),
            'placeholder' => null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function numericOperators(): array
    {
        return [
            'eq' => '=',
            'gt' => '>',
            'ge' => '≥',
            'le' => '≤',
            'lt' => '<',
            'ne' => '≠',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function stringOperators(): array
    {
        return [
            'eq' => '=',
            'ne' => '≠',
            'like' => '≈',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function booleanField(string $name, string $label): array
    {
        return [
            'kind' => 'select',
            'name' => $name,
            'label' => $label,
            'layout' => 'w33',
            'options' => [
                '' => '',
                '1' => __('Yes'),
                '0' => __('No'),
            ],
            'selected' => $this->postString($name) ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function multiEditorField(): array
    {
        return [
            'kind' => 'checkbox',
            'name' => 'send-to-multi-editor',
            'label' => '<span class="atRight" rel="tooltip" title="'
                . sprintf(__('Our multi editor is limited to a maximum of %d activities.'), SearchResults::MAX_LIMIT_FOR_MULTI_EDITOR)
                . '">' . __('Send to multi editor') . '</span>',
            'layout' => 'w33',
            'checked' => ! empty($_POST['send-to-multi-editor']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resultsPerPageField(): array
    {
        return [
            'kind' => 'select',
            'name' => 'resultsPerPage',
            'label' => __('Results per page'),
            'layout' => 'w33',
            'options' => [
                '10' => 10,
                '20' => 20,
                '50' => 50,
                '100' => 100,
                '200' => 200,
            ],
            'selected' => '20',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function submitField(): array
    {
        return [
            'kind' => 'submit',
            'layout' => 'w33 c',
            'value' => __('Search'),
        ];
    }

    private function postString(string $name): ?string
    {
        if (! isset($_POST[$name]) || is_array($_POST[$name])) {
            return null;
        }

        return (string) $_POST[$name];
    }

    /**
     * @return list<string>
     */
    private function postArray(string $name): array
    {
        if (! isset($_POST[$name]) || ! is_array($_POST[$name])) {
            return [];
        }

        return array_map('strval', array_values($_POST[$name]));
    }

    private function postOperator(string $name): string
    {
        if (! isset($_POST['opt'][$name]) || ! is_string($_POST['opt'][$name])) {
            return 'eq';
        }

        return $_POST['opt'][$name];
    }
}
