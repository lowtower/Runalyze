<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Metrics\Common\UnitInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

abstract class AbstractUnitBasedType extends AbstractType implements DataTransformerInterface
{
    protected int $ViewPrecision = 1;

    protected ?int $MaxViewPrecision = null;

    protected ?int $ModelPrecision = null;

    public function __construct(
        protected UnitInterface $Unit
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['input_unit'] = $this->Unit->getAppendix();
    }

    public function transform(mixed $value): ?string
    {
        if ($value === null) {
            return '';
        }

        $precision = $this->MaxViewPrecision === null ? $this->ViewPrecision : $this->getPrecisionFor($value);

        return number_format($this->Unit->fromBaseUnit((float) $value), $precision, '.', '');
    }

    protected function getPrecisionFor(mixed $value): int
    {
        return max(
            $this->ViewPrecision,
            min(
                $this->MaxViewPrecision,
                strlen(substr(strrchr((string) $value, '.'), 1))
            )
        );
    }

    public function reverseTransform($value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = $this->Unit->toBaseUnit((float) str_replace(',', '.', $value));

        return $this->ModelPrecision === null ? $value : round($value, $this->ModelPrecision);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getViewPrecision(): int
    {
        return $this->ViewPrecision;
    }

    public function getViewPrecisionFor(mixed $value): int
    {
        return $this->MaxViewPrecision === null ? $this->ViewPrecision : $this->getPrecisionFor($value);
    }
}
