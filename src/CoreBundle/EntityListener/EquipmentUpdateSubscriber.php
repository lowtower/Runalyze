<?php

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;

#[\Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener(event: 'onFlush')]
class EquipmentUpdateSubscriber
{
    protected EquipmentRepository $EquipmentRepository;

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $this->EquipmentRepository = $entityManager->getRepository(Equipment::class);

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Training) {
                $entityChangeSet = $unitOfWork->getEntityChangeSet($entity);

                $this->updateStatisticsForEquipment(
                    $entity,
                    new PreUpdateEventArgs($entity, $entityManager, $entityChangeSet)
                );
            }
        }
    }

    protected function updateStatisticsForEquipment(Training $activity, PreUpdateEventArgs $args)
    {
        $newEquipment = [];
        $removedEquipment = [];
        $objectManager = $args->getObjectManager();
        $unitOfWork = $objectManager->getUnitOfWork();
        $updates = $unitOfWork->getScheduledCollectionUpdates();

        foreach ($updates as $collection) {
            /** @var PersistentCollection $collection */
            if (
                $collection->isDirty() && $collection->getTypeClass()
                    ->getName() == Equipment::class &&
                $collection->getOwner() instanceof Training && $collection->getOwner()
                    ->getId() == $activity->getId()
            ) {
                $newEquipment = $collection->getInsertDiff();
                $removedEquipment = $collection->getDeleteDiff();
            }
        }

        $handledIds = array_map(fn (Equipment $equipment): ?int => $equipment->getId(), array_merge($newEquipment, $removedEquipment));

        if (! empty($removedEquipment)) {
            $this->EquipmentRepository->updateEquipment($removedEquipment, -1 * (int) $activity->getS(), -1 * $activity->getDistance(), false);
        }

        if (! empty($newEquipment)) {
            $this->EquipmentRepository->updateEquipment($newEquipment, (int) $activity->getS(), $activity->getDistance(), false);
        }

        $durationChange = $args->hasChangedField('s') ? $args->getNewValue('s') - $args->getOldValue('s') : 0;
        $distanceChange = $args->hasChangedField('distance') ? $args->getNewValue('distance') - $args->getOldValue('distance') : 0.0;
        $unchangedEquipment = [];

        if ($durationChange != 0 || $distanceChange != 0.0) {
            /** @var Equipment[] $unchangedEquipment */
            $unchangedEquipment = $activity->getEquipment()
                ->toArray();

            foreach ($unchangedEquipment as $i => $equipment) {
                if (in_array($equipment->getId(), $handledIds)) {
                    unset($unchangedEquipment[$i]);
                }
            }

            $this->EquipmentRepository->updateEquipment($unchangedEquipment, $durationChange, $distanceChange, false);
        }

        $objectManager = $args->getObjectManager();
        $metaData = $objectManager->getClassMetadata(Equipment::class);
        $unitOfWork = $objectManager->getUnitOfWork();

        foreach (array_merge($removedEquipment, $newEquipment, $unchangedEquipment) as $equipment) {
            $unitOfWork->computeChangeSet($metaData, $equipment);
        }
    }
}
