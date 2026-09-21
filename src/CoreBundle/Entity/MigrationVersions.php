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

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
