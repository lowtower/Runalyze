<?php

namespace Runalyze\Bundle\CoreBundle\Form\Tools\Anova;

use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\QueryGroups;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TypeRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AnovaType extends AbstractType
{
    public function __construct(
        protected SportRepository $SportRepository,
        protected TypeRepository $TypeRepository,
        protected EquipmentTypeRepository $EquipmentTypeRepository,
        protected TokenStorageInterface $TokenStorageInterface,
        protected ConfigurationManager $ConfigurationManager
    ) {}

    protected function getAccount(): Account
    {
        $account = $this->TokenStorageInterface->getToken() instanceof \Symfony\Component\Security\Core\Authentication\Token\TokenInterface ? $this->TokenStorageInterface->getToken()
            ->getUser() : null;

        if (! ($account instanceof Account)) {
            throw new \RuntimeException('Anova type must have a valid account token.');
        }

        return $account;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateFrom', DateType::class, [
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'pick-a-date small-size',
                ],
                'model_timezone' => 'UTC',
                'view_timezone' => 'UTC',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('dateTo', DateType::class, [
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'pick-a-date small-size',
                ],
                'model_timezone' => 'UTC',
                'view_timezone' => 'UTC',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('sport', ChoiceType::class, [
                'multiple' => true,
                'choices' => $this->SportRepository->findAllFor($this->getAccount()),
                'choice_label' => function ($sport, $key, $index) {
                    /** @var Sport $sport */
                    return $sport->getName();
                },
                'choice_translation_domain' => false,
                'attr' => [
                    'data-placeholder' => __('Choose sport(s)'),
                    'class' => 'chosen-select full-size',
                ],
                'choice_attr' => function ($sport, $key, $index): array {
                    /** @var Sport $sport */
                    return [
                        'data-id' => $sport->getId(),
                    ];
                },
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'choices' => $this->TypeRepository->findAllFor($this->getAccount()),
                'choice_label' => function ($type, $key, $index) {
                    /** @var Type $type */
                    return $type->getName();
                },
                'choice_translation_domain' => false,
                'attr' => [
                    'data-placeholder' => __('Choose activity type(s)'),
                    'class' => 'chosen-select full-size',
                ],
                'choice_attr' => function ($type, $key, $index): array {
                    /** @var Type $type */
                    return [
                        'data-sportid' => $type->getSport()
                            ->getId(),
                    ];
                },
            ])
            ->add('valueToGroupBy', ChoiceType::class, [
                'choices' => [
                    'Time related' => [
                        'Month' => QueryGroups::MONTH,
                        'Year' => QueryGroups::YEAR,
                    ],
                    'General groups' => [
                        'Sport type' => QueryGroups::SPORT,
                        'Activity type' => QueryGroups::TYPE,
                    ],
                    'Equipment types' => $this->getGroupsForSingleChoiceEquipmentTypes(),
                ],
            ])
            ->add('valueToLookAt', ChoiceType::class, [
                'choices' => [
                    'Main values' => [
                        'Pace' => QueryValues::PACE,
                        'Distance' => QueryValues::DISTANCE,
                        'Duration' => QueryValues::DURATION,
                        'Heart rate' => QueryValues::HEART_RATE_AVERAGE,
                        'TRIMP' => QueryValues::TRIMP,
                        'Power' => QueryValues::POWER,
                        'Cadence' => QueryValues::CADENCE,
                        'RPE' => QueryValues::RPE,
                        'Effective VO2max' => $this->getQueryValueEnumForVO2max(),
                    ],
                    'Running dynamics' => [
                        'Ground contact time' => QueryValues::GROUND_CONTACT_TIME,
                        'Ground contact balance' => QueryValues::GROUND_CONTACT_BALANCE,
                        'Vertical oscillation' => QueryValues::VERTICAL_OSCILLATION,
                        'Flight time' => QueryValues::FLIGHT_TIME,
                        'Flight ratio' => QueryValues::FLIGHT_RATIO,
                    ],
                    'RunScribe' => [
                        'Impact Gs (left)' => QueryValues::IMPACT_GS_LEFT,
                        'Impact Gs (right)' => QueryValues::IMPACT_GS_RIGHT,
                        'Braking Gs (left)' => QueryValues::BRAKING_GS_LEFT,
                        'Braking Gs (right)' => QueryValues::BRAKING_GS_RIGHT,
                        'Footstrike type (left)' => QueryValues::FOOTSTRIKE_TYPE_LEFT,
                        'Footstrike type (right)' => QueryValues::FOOTSTRIKE_TYPE_RIGHT,
                        'Pronation excursion (left)' => QueryValues::PRONATION_EXCURSION_LEFT,
                        'Pronation excursion (right)' => QueryValues::PRONATION_EXCURSION_RIGHT,
                    ],
                    'Weather' => [
                        'Temperature' => QueryValues::WEATHER_TEMPERATURE,
                        'Humidity' => QueryValues::WEATHER_HUMIDITY,
                        'Pressure' => QueryValues::WEATHER_PRESSURE,
                    ],
                ],
            ])
        ;
    }

    protected function getGroupsForSingleChoiceEquipmentTypes(): array
    {
        $equipmentTypes = $this->EquipmentTypeRepository->findSingleChoiceTypesFor($this->getAccount());
        $groups = [];

        foreach ($equipmentTypes as $equipmentType) {
            $groups[$equipmentType->getName()] = QueryGroups::getEnumForEquipmentType($equipmentType);
        }

        return $groups;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnovaData::class,
        ]);
    }

    protected function getQueryValueEnumForVO2max(): string
    {
        if ($this->ConfigurationManager->getList()->useVO2maxCorrectionForElevation()) {
            return QueryValues::VO2MAX_WITH_ELEVATION;
        }

        return QueryValues::VO2MAX;
    }
}
