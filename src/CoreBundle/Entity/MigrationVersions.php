<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'migration_versions')]
#[ORM\Entity]
class MigrationVersions
{
    #[ORM\Column(name: 'version', type: 'string', length: 255, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private string $version = '';

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
