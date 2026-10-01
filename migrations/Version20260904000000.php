<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20260904000000 extends AbstractMigration
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
        return "Renames the Equipment<->Sport join table from the misspelled `equipment_spor` to `equipment_to_sport`.\nThe intended name `equipment_sport` is already used by the unrelated EquipmentType<->Sport join table (columns sportid, equipment_typeid),\nso we cannot simply fix the typo without colliding with that table. ";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE ' . $this->DatabasePrefix . 'equipment_spor TO ' . $this->DatabasePrefix . 'equipment_to_sport');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE ' . $this->DatabasePrefix . 'equipment_to_sport TO ' . $this->DatabasePrefix . 'equipment_spor');
    }
}
