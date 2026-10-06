<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\AbstractTokenStorageAwareType;
use Runalyze\Bundle\CoreBundle\Form\ConfigurationManagerAwareTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityTypeChoiceType extends AbstractTokenStorageAwareType
{
    use ConfigurationManagerAwareTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->getConfigurationList()
                ->getActivityForm()
                ->getDatabaseOrderForTypes()
                ->sortCollection(
                    $this->getAccount()
                        ->getActivityTypes()
                ),
            'choice_label' => fn (Type $type, $key, $index): string => $type->getName(),
            'choice_value' => fn (?Type $type = null): int|null|string => $type instanceof Type ? $type->getId() : '',
            'choice_attr' => fn (Type $type, $key, $index): array => [
                'data-sport' => $type->getSport()
                    ->getId(),
            ],
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
