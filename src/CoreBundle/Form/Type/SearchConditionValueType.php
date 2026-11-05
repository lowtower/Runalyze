<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Text input for a search condition value, optionally wrapped together
 * with its unit (uses the input_unit variable of the global form theme).
 */
class SearchConditionValueType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['input_unit'] = $options['unit'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('unit');
        $resolver->setAllowedTypes('unit', 'string');
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
