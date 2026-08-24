<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Share your feedback here',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'An empty feedback makes no sense.',
                    ]),
                ],
            ]);
    }

    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => true,
        ]);
    }
}
