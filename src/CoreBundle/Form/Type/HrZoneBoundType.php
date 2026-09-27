<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Calculation\Activity\HrZonesCalculator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class HrZoneBoundType extends AbstractType implements DataTransformerInterface
{
    protected bool $IsRequired = false;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this);

        if (isset($options['required'])) {
            $this->IsRequired = $options['required'];
        }
    }

    public function setRequired(bool $flag = true): void
    {
        $this->IsRequired = $flag;
    }

    public function transform(mixed $zone): ?string
    {
        return $zone;
    }

    /**
     * @throws TransformationFailedException
     */
    public function reverseTransform($zone)
    {
        if ($zone === null) {
            if ($this->IsRequired) {
                throw new TransformationFailedException('Zone cannot be empty');
            }

            return null;
        }

        if (HrZonesCalculator::validateInputZone($zone)) {
            throw new TransformationFailedException('Zone is invalid');
        }

        return $zone;
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
