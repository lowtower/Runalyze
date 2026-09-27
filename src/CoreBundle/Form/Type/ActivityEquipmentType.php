<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Form\ConfigurationManagerAwareTrait;
use Runalyze\Bundle\CoreBundle\Form\TokenStorageAwareTypeTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityEquipmentType extends AbstractType implements DataTransformerInterface
{
    use ConfigurationManagerAwareTrait;
    use TokenStorageAwareTypeTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $equipmentCategories = $this->getAccount()
            ->getEquipmentTypes();
        $accountEquipment = $this->getConfigurationList()
            ->getActivityForm()
            ->getDatabaseOrderForEquipment()
            ->sortCollection(
                $this->getAccount()
                    ->getEquipment()
            );

        $equipment = [];
        /** @var Equipment $acEqp */
        foreach ($accountEquipment as $acEqp) {
            $equipment[$acEqp->getType()->getId()][] = $acEqp;
        }

        /** @var EquipmentType $category */
        foreach ($equipmentCategories as $category) {
            if (isset($equipment[$category->getId()])) {
                $isSingleChoice = $category->getInput() == EquipmentType::CHOICE_SINGLE;

                $builder->add((string) $category->getId(), EntityType::class, [
                    'class' => Equipment::class,
                    'choices' => $equipment[$category->getId()],
                    'choice_label' => 'name',
                    'choice_attr' => fn (Equipment $eqp, $key, $index): array => $this->getChoiceAttributesForEquipment($eqp),
                    'choice_translation_domain' => false,
                    'translation_domain' => false,
                    'label' => $category->getName(),
                    'multiple' => ! $isSingleChoice,
                    'expanded' => ! $isSingleChoice,
                    'required' => false,
                ]);
            }
        }

        $builder->addViewTransformer($this);
    }

    public function transform(mixed $value): ?array
    {
        if (! ($value instanceof Collection)) {
            return null;
        }

        $categories = [];

        foreach ($value as $equipment) {
            if ($equipment->getType()->getInput() == EquipmentType::CHOICE_SINGLE) {
                $categories[$equipment->getType()->getId()] = $equipment;
            } else {
                $categories[$equipment->getType()->getId()] ??= new ArrayCollection();
                $categories[$equipment->getType()->getId()][] = $equipment;
            }
        }

        return $categories;
    }

    public function reverseTransform(mixed $value): ArrayCollection
    {
        $equipment = new ArrayCollection();

        if (is_array($value)) {
            foreach ($value as $subValue) {
                if ($subValue instanceof Collection) {
                    foreach ($subValue as $object) {
                        $equipment[] = $object;
                    }
                } elseif ($subValue !== null) {
                    $equipment[] = $subValue;
                }
            }
        }

        return $equipment;
    }

    /**
     * @return array<string, string>
     */
    protected function getChoiceAttributesForEquipment(Equipment $equipment): array
    {
        return [
            'data-start' => $equipment->getDateStart() instanceof DateTime ? $equipment->getDateStart()
                ->format('Y-m-d') : '',
            'data-end' => $equipment->getDateEnd() instanceof DateTime ? $equipment->getDateEnd()
                ->format('Y-m-d') : '',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
