<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PercentageType extends AbstractType implements DataTransformerInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['input_unit'] = '%';
    }

    public function transform(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    public function reverseTransform($value): mixed
    {
        return $value === null ? null : (float) str_replace(',', '.', $value);
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
        return TextType::class;
    }
}
