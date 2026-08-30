<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Metrics\Velocity\Unit\AbstractPaceInDecimalFormatUnit;
use Runalyze\Metrics\Velocity\Unit\KilometerPerHour;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class WindSpeedType extends AbstractUnitBasedType
{
    /**
     * @var int
     */
    protected $ViewPrecision = 0;

    /**
     * @var AbstractPaceInDecimalFormatUnit
     */
    protected $ModelUnit;

    public function __construct(AbstractPaceInDecimalFormatUnit $velocityUnit)
    {
        $this->ModelUnit = new KilometerPerHour();

        parent::__construct($velocityUnit);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        /** @var AbstractPaceInDecimalFormatUnit $modelUnit */
        $modelUnit = $this->ModelUnit;
        /** @var AbstractPaceInDecimalFormatUnit $unit */
        $unit = $this->Unit;

        $view->vars['attr']['min'] = 0;
        $view->vars['attr']['data-base-unit-factor'] = $modelUnit->getDividendFromBaseUnit() / $unit->getDividendFromBaseUnit();
    }

    public function transform($value): ?string
    {
        if ($value !== null) {
            if ((int) $value === 0) {
                return '0';
            }

            $value = $this->ModelUnit->toBaseUnit((int) $value);
        }

        return $value === null ? null : (string) round($this->Unit->fromBaseUnit($value));
    }

    public function reverseTransform($value)
    {
        if ($value !== null) {
            if ((int) $value === 0) {
                return 0;
            }

            $value = $this->Unit->toBaseUnit((int) $value);
        }

        return $value === null ? null : (int) round($this->ModelUnit->fromBaseUnit($value));
    }

    public function getParent(): string
    {
        return IntegerType::class;
    }
}
