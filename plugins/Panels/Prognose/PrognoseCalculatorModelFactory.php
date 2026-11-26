<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose;

use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Model\Prognosis\PrognosisModel;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;

final readonly class PrognoseCalculatorModelFactory
{
    public function normalizeModel(string $model): string
    {
        return match ($model) {
            'robert-bock', 'herbert-steffny', 'david-cameron' => $model,
            default => 'vo2max',
        };
    }

    /**
     * @param array<string, mixed> $input Validated by the existing calculator form
     */
    public function create(array $input): PrognosisInterface
    {
        $model = match ($this->normalizeModel((string) ($input['model'] ?? ''))) {
            'robert-bock' => 'cpp',
            'herbert-steffny' => 'steffny',
            'david-cameron' => 'cameron',
            default => 'vo2max',
        };

        $results = [];

        if ($model !== 'vo2max') {
            $results[] = [
                'distance' => (float) str_replace(',', '.', (string) $input['best-result-km']),
                's' => new Duration($input['best-result-time'])->seconds(),
            ];

            if ($model === 'cpp') {
                $results[] = [
                    'distance' => (float) str_replace(',', '.', (string) $input['second-best-result-km']),
                    's' => new Duration($input['second-best-result-time'])->seconds(),
                ];
            }
        }

        return new PrognosisModel(
            $model,
            $results,
            (float) str_replace(',', '.', (string) ($input['vo2max'] ?? 0)),
            (int) ($input['endurance-value'] ?? 0),
            isset($input['endurance'])
        )->getPrognosisObject();
    }
}
