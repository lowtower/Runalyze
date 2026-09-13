<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Data\RPE;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RpeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => array_flip(RPE::completeList()),
            'choice_translation_domain' => false,
            'placeholder' => '-',
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
