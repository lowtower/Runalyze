<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @author https://github.com/avanzu/Symfony-Doctrine-Prefix-Bundle/blob/master/Subscriber/TablePrefixSubscriber.php
 */
class TablePrefixSubscriber implements \Doctrine\Common\EventSubscriber
{
    /**
     * @var string
     */
    protected $prefix = '';

    public function __construct($prefix = '')
    {
        $this->prefix = (string) $prefix;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $classMetadata */
        $classMetadata = $eventArgs->getClassMetadata();

        if (strlen($this->prefix)) {
            if (strpos($classMetadata->getTableName(), $this->prefix) !== 0) {
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

                if (strpos($mappedTableName, $this->prefix) !== 0) {
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return ['loadClassMetadata'];
    }
}
