<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\AbstractTokenStorageAwareType;
use Runalyze\Bundle\CoreBundle\Form\ConfigurationManagerAwareTrait;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActivityTypeChoiceType extends AbstractTokenStorageAwareType
{
    use ConfigurationManagerAwareTrait;

    public function __construct(TokenStorageInterface $tokenStorage, ConfigurationManager $configurationManager)
    {
        parent::__construct($tokenStorage);
        $this->setConfigurationManager($configurationManager);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => fn (Options $options): Collection => $this->getConfigurationList()
                ->getActivityForm()
                ->getDatabaseOrderForTypes()
                ->sortCollection(
                    $this->getAccount()
                        ->getActivityTypes()
                ),
            'choice_label' => fn (Type $type, $key, $index): string => $type->getName(),
            'choice_value' => fn (?Type $type = null): int|string|null => $type instanceof Type ? $type->getId() : '',
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
