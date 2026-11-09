<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Model\Prognosis;

use Runalyze\Sports\Running\Prognosis\Bock;
use Runalyze\Sports\Running\Prognosis\Cameron;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;
use Runalyze\Sports\Running\Prognosis\Steffny;
use Runalyze\Sports\Running\Prognosis\VO2max;

/**
 * Prognosis model selection.
 *
 * Replaces the model switch of the former plugin panel method `prepareForPrognosis()`.
 */
class PrognosisModel
{
    /**
     * @param string $model prognosis model, one of `vo2max`, `cpp`, `steffny`, `cameron`
     * @param array<int, array<string, int|float|null>> $topResults rows as returned by TrainingRepository::findTopResults()
     * @param float $vo2maxShape current effective VO2max shape [ml/kg/min]
     * @param int $basicEndurance current basic endurance [%]
     * @param bool $useBasicEndurance use the basic endurance factor for long distances (VO2max model only)
     */
    public function __construct(
        private readonly string $model,
        private readonly array $topResults,
        private readonly float $vo2maxShape,
        private readonly int $basicEndurance,
        private readonly bool $useBasicEndurance
    ) {}

    public function getPrognosisObject(): PrognosisInterface
    {
        return match ($this->model) {
            'cpp' => $this->buildBock(),
            'steffny' => $this->buildSteffny(),
            'cameron' => $this->buildCameron(),
            default => $this->buildVO2max(),
        };
    }

    private function buildBock(): Bock
    {
        $prognosis = new Bock();

        if (count($this->topResults) === 2) {
            $prognosis->setFromResults(
                (float) $this->topResults[0]['distance'],
                (float) $this->topResults[0]['s'],
                (float) $this->topResults[1]['distance'],
                (float) $this->topResults[1]['s']
            );
        }

        return $prognosis;
    }

    private function buildSteffny(): Steffny
    {
        $prognosis = new Steffny();

        if (count($this->topResults) === 2) {
            $prognosis->setReferenceResult(
                (float) $this->topResults[0]['distance'],
                (float) $this->topResults[0]['s']
            );
        }

        return $prognosis;
    }

    private function buildCameron(): Cameron
    {
        $prognosis = new Cameron();

        if (count($this->topResults) === 2) {
            $prognosis->setReferenceResult(
                (float) $this->topResults[0]['distance'],
                (float) $this->topResults[0]['s']
            );
        }

        return $prognosis;
    }

    private function buildVO2max(): VO2max
    {
        return new VO2max($this->vo2maxShape, $this->useBasicEndurance, $this->basicEndurance);
    }
}
