<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use Ajax;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Util\LocalTime;
use Twig\Environment;

final readonly class PrognoseCalculatorWindowRenderer
{
    private const int DISTANCE_PRECISION = 2;

    public function __construct(
        private PrognoseCalculatorFormInput $formInput,
        private PrognoseCalculatorModelFactory $modelFactory,
        private PrognoseCalculatorPredictionRows $predictionRows,
        private PrognoseCalculatorResultTableRenderer $resultTableRenderer,
        private PrognoseCalculatorModelHintRenderer $modelHintRenderer,
        private Environment $twig
    ) {}

    /**
     * @param array<int, array<string, int|float|null>> $topResults
     * @param list<float|int> $configuredDistances
     * @param array<string, mixed> $submittedInput
     */
    public function render(
        Account $account,
        int $runningSportId,
        array $topResults,
        array $configuredDistances,
        array $submittedInput,
        float $currentShape,
        int $marathonShape,
        DistanceUnitSystem $unitSystem,
        int $legacyPaceUnit
    ): string {
        $input = $this->formInput->prepare(
            $submittedInput,
            $topResults,
            $configuredDistances,
            $currentShape,
            $marathonShape,
            $unitSystem
        );

        $input['model'] = $this->modelFactory->normalizeModel((string) ($input['model'] ?? ''));
        $prognosis = $this->modelFactory->create($input);

        $distances = array_map(
            static fn (string $value): float|string => $unitSystem->isImperial()
                ? round((float) $value / DistanceUnitSystem::MILE_MULTIPLIER, self::DISTANCE_PRECISION)
                : $value,
            array_map(trim(...), explode(',', (string) ($input['distances'] ?? '')))
        );

        $rows = $this->predictionRows->calculate(
            $account,
            $runningSportId,
            $distances,
            $prognosis,
            $legacyPaceUnit,
            $unitSystem
        );

        $resultTable = $this->resultTableRenderer->render($rows, $prognosis->areValuesValid());
        $resultTable .= $this->modelHintRenderer->render($input['model'], $prognosis);

        $infoLines = $this->buildInfoLines($topResults, $currentShape, $marathonShape, $unitSystem);

        $input['best-result-km'] = new Distance((float) ($input['best-result-km'] ?? 0), $unitSystem)->string(false, self::DISTANCE_PRECISION);
        $input['second-best-result-km'] = new Distance((float) ($input['second-best-result-km'] ?? 0), $unitSystem)->string(false, self::DISTANCE_PRECISION);

        $html = $this->twig->render('@Prognose/calculator_form.html.twig', [
            'input' => $input,
            'distance_unit' => $unitSystem->distanceUnit(),
            'info_lines' => $infoLines,
            'result_table' => $resultTable,
        ]);

        return $html . Ajax::wrapJSasFunction(
            "$('#prognosis-calculator .hide-on-model-change:not(.only-" . $input['model'] . ")').closest('div').hide();"
            . "$('#prognosis-model').on('change',function(){"
            . "$('#prognosis-calculator .hide-on-model-change').closest('div').hide();"
            . "$('#prognosis-calculator .only-'+$(this).val()).closest('div').show();"
            . '});'
        );
    }

    /**
     * @param array<int, array<string, int|float|null>> $topResults
     *
     * @return array<string, string>
     */
    private function buildInfoLines(array $topResults, float $currentShape, int $marathonShape, DistanceUnitSystem $unitSystem): array
    {
        $infoLines = [
            'vo2max' => __('Your current effective VO2max:') . ' ' . $currentShape . '. '
                . __('Your current marathon shape:') . ' ' . $marathonShape . ' %.',
        ];

        if (count($topResults) >= 2) {
            $resultLine = sprintf(
                __('%s in %s <small>(%s)</small> and %s in %s <small>(%s)</small>'),
                new Distance((float) $topResults[0]['distance'], $unitSystem)->string(),
                Duration::format($topResults[0]['s']),
                new LocalTime($topResults[0]['time'])->format('d.m.Y'),
                new Distance((float) $topResults[1]['distance'], $unitSystem)->string(),
                Duration::format($topResults[1]['s']),
                new LocalTime($topResults[1]['time'])->format('d.m.Y')
            );
        } elseif (count($topResults) === 1) {
            $resultLine = sprintf(
                __('%s in %s <small>(%s)</small>'),
                new Distance((float) $topResults[0]['distance'], $unitSystem)->string(),
                Duration::format($topResults[0]['s']),
                new LocalTime($topResults[0]['time'])->format('d.m.Y')
            );
        } else {
            $resultLine = __('none');
        }

        $infoLines['robert-bock'] = __('Your two best results:') . ' ' . $resultLine;

        return $infoLines;
    }
}
