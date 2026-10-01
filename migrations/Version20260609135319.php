<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class Version20260609135319 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly string $DatabasePrefix
    ) {
        parent::__construct($connection, $logger);
    }

    public function getDescription(): string
    {
        return 'Fix ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor DROP FOREIGN KEY ' . $this->DatabasePrefix . 'equipment_spor_ibfk_1');
        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor DROP FOREIGN KEY ' . $this->DatabasePrefix . 'equipment_spor_ibfk_2');

        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor ADD PRIMARY KEY (equipment_id, sportid)');

        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor
            ADD CONSTRAINT FK_equipment
            FOREIGN KEY (equipment_id) REFERENCES ' . $this->DatabasePrefix . 'equipment (id)
            ON DELETE CASCADE');

        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor
            ADD CONSTRAINT FK_sport
            FOREIGN KEY (sportid) REFERENCES ' . $this->DatabasePrefix . 'sport (id)
            ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor DROP FOREIGN KEY FK_equipment');
        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor DROP FOREIGN KEY FK_sport');

        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor ADD PRIMARY KEY (sportid, equipment_id)');

        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor
            ADD CONSTRAINT ' . $this->DatabasePrefix . 'equipment_spor_ibfk_1
            FOREIGN KEY (sportid) REFERENCES ' . $this->DatabasePrefix . 'sport (id)
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'equipment_spor
            ADD CONSTRAINT ' . $this->DatabasePrefix . 'equipment_spor_ibfk_2
            FOREIGN KEY (equipment_id) REFERENCES ' . $this->DatabasePrefix . 'equipment (id)
            ON DELETE CASCADE ON UPDATE CASCADE');
    }
}
