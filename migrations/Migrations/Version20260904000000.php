<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20260904000000 extends AbstractMigration implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getDescription(): string
    {
        return "Renames the Equipment<->Sport join table from the misspelled `equipment_spor` to `equipment_to_sport`.\nThe intended name `equipment_sport` is already used by the unrelated EquipmentType<->Sport join table (columns sportid, equipment_typeid),\nso we cannot simply fix the typo without colliding with that table. ";
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');

        $this->addSql('RENAME TABLE ' . $prefix . 'equipment_spor TO ' . $prefix . 'equipment_to_sport');
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');

        $this->addSql('RENAME TABLE ' . $prefix . 'equipment_to_sport TO ' . $prefix . 'equipment_spor');
    }
}
