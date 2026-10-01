<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;

class DisplayableVO2max extends DisplayableValue
{
    protected float $UncorrectedValue;

    protected string $IconClass;

    protected array $LowerLimitsForIcon = [3.0, 1.0, -1.0, -3.0];

    public function __construct(float $uncorrectedValue, RunalyzeConfigurationList $configurationList, bool $valueIsUsedForShape = true)
    {
        parent::__construct($uncorrectedValue, '', 2);

        $this->correctValue($configurationList);
        $this->setIconClass($configurationList, $valueIsUsedForShape);
    }

    /**
     * @return float [ml/kg/min]
     */
    public function getUncorrectedValue(): float
    {
        return $this->UncorrectedValue;
    }

    public function getIconClass(): string
    {
        return $this->IconClass;
    }

    protected function correctValue(RunalyzeConfigurationList $configurationList): void
    {
        $this->UncorrectedValue = $this->Value;
        $this->Value *= $configurationList->getVO2maxCorrectionFactor();
    }

    protected function setIconClass(RunalyzeConfigurationList $configurationList, bool $valueIsUsedForShape): void
    {
        $classes = [
            'vo2max-icon',
            'small',
            $this->getIconClassFor($this->Value - $configurationList->getCurrentVO2maxShape()),
        ];

        if (! $valueIsUsedForShape) {
            $classes[] = 'unimportant';
        }

        $this->IconClass = implode(' ', $classes);
    }

    protected function getIconClassFor(float $diffToShape): string
    {
        if ($diffToShape > $this->LowerLimitsForIcon[0]) {
            return 'fa-arrow-up';
        }

        if ($diffToShape > $this->LowerLimitsForIcon[1]) {
            return 'fa-arrow-up  fa-rotate-45';
        }

        if ($diffToShape > $this->LowerLimitsForIcon[2]) {
            return 'fa-arrow-right';
        }

        if ($diffToShape > $this->LowerLimitsForIcon[3]) {
            return 'fa-arrow-right  fa-rotate-45';
        }

        return 'fa-arrow-down';
    }
}
