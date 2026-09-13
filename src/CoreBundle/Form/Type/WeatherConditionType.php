<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Data\Weather\Condition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WeatherConditionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => Condition::completeList(),
            'choice_label' => fn ($value, $key, $index): string => (new Condition($value))->string(),
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
