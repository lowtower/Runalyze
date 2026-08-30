<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20260609135319 extends AbstractMigration implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getDescription(): string
    {
        return 'Fix ';
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->container->getParameter('database_prefix');

        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor DROP FOREIGN KEY runalyze_equipment_spor_ibfk_1');
        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor DROP FOREIGN KEY runalyze_equipment_spor_ibfk_2');

        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor ADD PRIMARY KEY (equipment_id, sportid)');

        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor
            ADD CONSTRAINT FK_equipment
            FOREIGN KEY (equipment_id) REFERENCES ' . $prefix . 'equipment (id)
            ON DELETE CASCADE');

        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor
            ADD CONSTRAINT FK_sport
            FOREIGN KEY (sportid) REFERENCES ' . $prefix . 'sport (id)
            ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->container->getParameter('database_prefix');

        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor DROP FOREIGN KEY FK_equipment');
        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor DROP FOREIGN KEY FK_sport');

        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor ADD PRIMARY KEY (sportid, equipment_id)');

        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor
            ADD CONSTRAINT ' . $prefix . 'equipment_spor_ibfk_1
            FOREIGN KEY (sportid) REFERENCES ' . $prefix . 'sport (id)
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->addSql('ALTER TABLE ' . $prefix . 'equipment_spor
            ADD CONSTRAINT ' . $prefix . 'equipment_spor_ibfk_2
            FOREIGN KEY (equipment_id) REFERENCES ' . $prefix . 'equipment (id)
            ON DELETE CASCADE ON UPDATE CASCADE');
    }
}
