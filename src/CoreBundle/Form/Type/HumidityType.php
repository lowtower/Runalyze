<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HumidityType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['input_unit'] = '%';
        $view->vars['attr']['min'] = 0;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'scale' => 0,
            'type' => 'fractional',
            'compound' => false,
        ]);

        $resolver->setAllowedValues('type', [
            'fractional',
            'integer',
        ]);

        $resolver->setAllowedTypes('scale', 'int');
    }

    public function getParent(): string
    {
        return IntegerType::class;
    }
}
