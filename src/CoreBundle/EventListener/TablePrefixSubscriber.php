<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

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

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY) {
                if (
                    ! isset($classMetadata->associationMappings[$fieldName]['joinTable']) ||
                    ! isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])
                ) {
                    continue;
                }

                $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];

                if (! str_starts_with($mappedTableName, $this->prefix)) {
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
                }
            }
        }
    }
}
