<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Closure;
use Doctrine\Common\Collections\Collection;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\AbstractTokenStorageAwareType;
use Runalyze\Bundle\CoreBundle\Form\ConfigurationManagerAwareTrait;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SportChoiceType extends AbstractTokenStorageAwareType
{
    use ConfigurationManagerAwareTrait;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        protected SportRepository $SportRepository,
        ConfigurationManager $configurationManager
    ) {
        parent::__construct($tokenStorage);
        $this->setConfigurationManager($configurationManager);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => fn (Options $options): Collection => $this->getConfigurationList()
                ->getActivityForm()
                ->getDatabaseOrderForSport()
                ->sortCollection(
                    $this->getAccount()
                        ->getSports()
                ),
            'choice_label' => fn (Sport $sport, $key, $index): string => $sport->getName(),
            'choice_value' => fn (?Sport $sport = null): int|string|null => $sport instanceof Sport ? $sport->getId() : '',
            'choice_attr' => function (Options $options): Closure {
                $sportIds = $this->getAccount()
                    ->getSports()
                    ->map(fn (Sport $sport): int => $sport->getId())
                    ->toArray();
                $sportToEquipmentCategoryRelations = $this->SportRepository->findEquipmentCategoryIdsFor($sportIds);

                return function (Sport $sport, $key, $index) use ($sportToEquipmentCategoryRelations): array {
                    $sportId = $sport->getId();
                    $availableEquipmentTypes = [];

                    foreach ($sportToEquipmentCategoryRelations as $relation) {
                        if ($sportId == $relation['sport_id']) {
                            $availableEquipmentTypes[] = $relation['equipment_type_id'];
                        }
                    }

                    return [
                        'data-outside' => $sport->getOutside() ? '1' : '0',
                        'data-energy' => $sport->getKcal(),
                        'data-internal' => $sport->getInternalSportId() ?: '',
                        'data-distances' => $sport->getDistances() ? '1' : '0',
                        'data-speed' => $sport->getSpeed(),
                        'data-activity-type' => $sport->getDefaultType() instanceof Type ? $sport->getDefaultType()
                            ->getId() : '',
                        'data-equipment-types' => json_encode($availableEquipmentTypes),
                        'data-privacy' => $sport->getDefaultPrivacy() ? '1' : '0',
                        'data-power' => $sport->getPower() ? '1' : '0',
                    ];
                };
            },
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
