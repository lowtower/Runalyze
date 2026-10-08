<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;

/**
 * @author https://github.com/avanzu/Symfony-Doctrine-Prefix-Bundle/blob/master/Subscriber/TablePrefixSubscriber.php
 */
#[\Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener(event: 'loadClassMetadata')]
class TablePrefixSubscriber
{
    protected string $prefix;

    public function __construct($prefix = '')
    {
        $this->prefix = (string) $prefix;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $eventArgs->getClassMetadata();

        if (strlen($this->prefix)) {
            if (! str_starts_with($classMetadata->getTableName(), $this->prefix)) {
                $classMetadata->setPrimaryTable([
                    'name' => $this->prefix . $classMetadata->getTableName(),
                ]);
            }
        }

        foreach ($classMetadata->getAssociationMappings() as $mapping) {
            // only the owning side of a many-to-many association has a join table
            if (! $mapping instanceof ManyToManyOwningSideMapping) {
                continue;
            }

            if (! str_starts_with($mapping->joinTable->name, $this->prefix)) {
                $mapping->joinTable->name = $this->prefix . $mapping->joinTable->name;
            }
        }
    }
}
