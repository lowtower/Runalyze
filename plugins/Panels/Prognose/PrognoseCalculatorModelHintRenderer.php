<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose;

use HTML;
use Runalyze\Sports\Running\Prognosis\Bock;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;
use Runalyze\Sports\Running\Prognosis\VO2max;

final readonly class PrognoseCalculatorModelHintRenderer
{
    public function render(string $model, PrognosisInterface $prognosis): string
    {
        if ($model === 'robert-bock' && $prognosis instanceof Bock) {
            return $this->renderRobertBock($prognosis);
        }

        if ($model === 'vo2max' && $prognosis instanceof VO2max && ! $prognosis->areValuesValid()) {
            return HTML::warning(sprintf(
                __('Effective VO2max must be between %u and %u.'),
                VO2max::REASONABLE_VO2MAX_MINIMUM,
                VO2max::REASONABLE_VO2MAX_MAXIMUM
            ));
        }

        return '';
    }

    private function renderRobertBock(Bock $prognosis): string
    {
        $html = HTML::info(sprintf(
            __('The results give the constants K = %f and e = %f.'),
            $prognosis->getK(),
            $prognosis->getE()
        )) . '<br>';

        if (! $prognosis->areValuesValid()) {
            $html .= HTML::warning(sprintf(
                __('K must be between %u and %u, e between %f and %f.'),
                Bock::K_LOWER_BOUND,
                Bock::K_UPPER_BOUND,
                Bock::E_LOWER_BOUND,
                Bock::E_UPPER_BOUND
            ));
        }

        return $html;
    }
}
