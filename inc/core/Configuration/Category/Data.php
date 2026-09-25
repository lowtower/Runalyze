<?php

namespace Runalyze\Configuration\Category;

use DB;
use Helper;
use Runalyze\Calculation\BasicEndurance;
use Runalyze\Calculation\JD;
use Runalyze\Calculation\Performance;
use Runalyze\Configuration;
use Runalyze\Parameter\FloatingPoint;
use Runalyze\Parameter\Integer;
use Runalyze\Sports\Performance\Model\TsbModel;
use SessionAccountHandler;

class Data extends \Runalyze\Configuration\Category
{
    protected function key(): string
    {
        return 'data';
    }

    protected function createHandles()
    {
        $this->createHandle('START_TIME', new Integer(0));

        $this->createHandle('HF_MAX', new Integer(200));
        $this->createHandle('HF_REST', new Integer(60));

        $this->createHandle('VO2MAX_FORM', new FloatingPoint(0.0));
        $this->createHandle('VO2MAX_CORRECTOR', new FloatingPoint(1.0));

        $this->createHandle('BASIC_ENDURANCE', new Integer(0));

        $this->createHandle('MAX_ATL', new Integer(0));
        $this->createHandle('MAX_CTL', new Integer(0));
        $this->createHandle('MAX_TRIMP', new Integer(0));
    }

    /**
     * @return int
     */
    public function startTime()
    {
        return $this->get('START_TIME');
    }

    /**
     * @param int $starttime timestamp
     */
    public function updateStartTime($starttime): void
    {
        $this->object('START_TIME')
            ->set($starttime);
        $this->updateValue($this->handle('START_TIME'));
    }

    public function recalculateStartTime(): void
    {
        $this->updateStartTime(
            DB::getInstance()->query('SELECT MIN(`time`) FROM `' . PREFIX . 'training` WHERE accountid = ' . SessionAccountHandler::getId())->fetchColumn()
        );
    }

    /**
     * @return int [bpm]
     */
    public function HRmax()
    {
        return $this->get('HF_MAX');
    }

    /**
     * @param int $heartrate in [bpm]
     */
    public function updateHRmax($heartrate): void
    {
        $this->object('HF_MAX')
            ->set($heartrate);
        $this->updateValue($this->handle('HF_MAX'));
    }

    /**
     * @return int [bpm]
     */
    public function HRrest()
    {
        return $this->get('HF_REST');
    }

    /**
     * @param int $heartrate in [bpm]
     */
    public function updateHRrest($heartrate): void
    {
        $this->object('HF_REST')
            ->set($heartrate);
        $this->updateValue($this->handle('HF_REST'));
    }

    /**
     * @return float [ml/kg/min]
     */
    public function vo2maxShape()
    {
        return $this->get('VO2MAX_FORM');
    }

    /**
     * @return float [ml/kg/min]
     */
    public function vo2max()
    {
        if (Configuration::VO2max()->useManualValue()) {
            return Configuration::VO2max()->manualValue();
        }

        return $this->vo2maxShape();
    }

    /**
     * @param float $shape [ml/kg/min]
     */
    public function updateVO2maxShape($shape): void
    {
        $this->object('VO2MAX_FORM')
            ->set($shape);
        $this->updateValue($this->handle('VO2MAX_FORM'));
    }

    /**
     * @return float
     */
    public function vo2maxCorrector()
    {
        return $this->get('VO2MAX_CORRECTOR');
    }

    /**
     * @return float
     */
    public function vo2maxCorrectionFactor()
    {
        if (Configuration::VO2max()->useManualFactor()) {
            return Configuration::VO2max()->manualFactor();
        }

        return $this->vo2maxCorrector();
    }

    /**
     * @param float $factor
     */
    public function updateVO2maxCorrector($factor): void
    {
        $this->object('VO2MAX_CORRECTOR')
            ->set($factor);
        $this->updateValue($this->handle('VO2MAX_CORRECTOR'));
    }

    /**
     * @return int
     */
    public function basicEndurance()
    {
        return $this->get('BASIC_ENDURANCE');
    }

    /**
     * @param int $basicEndurance
     */
    public function updateBasicEndurance($basicEndurance): void
    {
        $this->object('BASIC_ENDURANCE')
            ->set($basicEndurance);
        $this->updateValue($this->handle('BASIC_ENDURANCE'));
    }

    public function maxATL(): int
    {
        return max(1, $this->get('MAX_ATL'));
    }

    public function updateMaxATL(int $atl): void
    {
        $this->object('MAX_ATL')
            ->set($atl);
        $this->updateValue($this->handle('MAX_ATL'));
    }

    public function maxCTL(): int
    {
        return max(1, $this->get('MAX_CTL'));
    }

    /**
     * @param int $ctl
     */
    public function updateMaxCTL($ctl): void
    {
        $this->object('MAX_CTL')
            ->set($ctl);
        $this->updateValue($this->handle('MAX_CTL'));
    }

    public function maxTrimp(): int
    {
        return max(1, $this->get('MAX_TRIMP'));
    }

    /**
     * @param int $trimp
     */
    public function updateMaxTrimp($trimp): void
    {
        $this->object('MAX_TRIMP')
            ->set($trimp);
        $this->updateValue($this->handle('MAX_TRIMP'));
    }

    public function recalculateMaxValues(): void
    {
        $Query = new Performance\ModelQuery();
        $Query->execute(\DB::getInstance());

        $Calc = new Performance\MaximumCalculator(fn (array $array): \Runalyze\Sports\Performance\Model\TsbModel => new TsbModel($array, Configuration::Trimp()->daysForCTL(), Configuration::Trimp()->daysForATL()), $Query->data());

        $this->updateMaxCTL($Calc->maxFitness());
        $this->updateMaxATL($Calc->maxFatigue());
        $this->updateMaxTrimp($Calc->maxTrimp());
    }

    /**
     * @return float new factor [ml/kg/min]
     */
    public function recalculateVO2maxCorrector(): float
    {
        $Corrector = new JD\LegacyEffectiveVO2maxCorrector();
        $Corrector->fromDatabase(
            DB::getInstance(),
            SessionAccountHandler::getId(),
            Configuration::General()->runningSport()
        );

        $this->updateVO2maxCorrector($Corrector->factor());

        return $Corrector->factor();
    }

    /**
     * @return float new shape [ml/kg/min]
     */
    public function recalculateVO2maxShape(): float
    {
        $Shape = new JD\Shape(
            DB::getInstance(),
            SessionAccountHandler::getId(),
            Configuration::General()->runningSport(),
            Configuration::VO2max()
        );
        $Shape->setCorrector(new JD\LegacyEffectiveVO2maxCorrector($this->vo2maxCorrectionFactor()));
        $Shape->calculate();

        $this->updateVO2maxShape($Shape->value());

        return $Shape->value();
    }

    /**
     * Recalculate required values
     *
     * Variables in this category store cached values.
     * They can be recalculated all together.
     */
    public function recalculateEverything(): void
    {
        $this->recalculateStartTime();
        $this->recalculateMaxValues();
        $this->recalculateVO2maxCorrector();
        $this->recalculateVO2maxShape();

        Helper::recalculateHFmaxAndHFrest();
        BasicEndurance::recalculateValue();
    }
}
