<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20260924120000 extends AbstractMigration implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getDescription(): string
    {
        return 'Normalize legacy training.wind_deg values outside the valid 0..359 range (>= 360 or negative) back into that range.';
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');

        $this->addSql('UPDATE `' . $prefix . 'training` SET wind_deg = MOD(MOD(wind_deg, 360) + 360, 360) WHERE wind_deg >= 360 OR wind_deg < 0');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'This data-cleanup migration is irreversible: normalized wind_deg values '
            .'(e.g. 360 -> 0, -10 -> 350) cannot be reliably distinguished from originally-valid readings.'
        );
    }
}
