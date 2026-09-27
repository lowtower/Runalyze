<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

class ActivityRoundType extends AbstractType implements DataTransformerInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('distance', DistanceType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'small-size',
                ],
                'label' => false,
            ])
            ->add('duration', DurationType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'small-size',
                ],
                'label' => false,
            ])
            ->add('intensity', ActivityLapIntensityChoiceType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'small-size',
                ],
                'label' => false,
            ])
        ;

        $builder->addViewTransformer($this);
    }

    public function transform(mixed $value): ?array
    {
        if (! ($value instanceof Round)) {
            return null;
        }

        return [
            'distance' => $value->getDistance(),
            'duration' => $value->getDuration(),
            'intensity' => $value->getIntensity(),
        ];
    }

    public function reverseTransform(mixed $value): ?Round
    {
        if (is_array($value) && isset($value['duration']) && array_key_exists('distance', $value)) {
            $r = new Round(
                $value['distance'],
                $value['duration']
            );
            $r->setIntensity($value['intensity']);

            return $r;
        }

        return null;
    }
}
