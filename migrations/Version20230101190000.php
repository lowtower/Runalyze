<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20230101190000 extends AbstractMigration
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
        return 'TSC: Add new table for equipment to sports assigment: equipment_spor (the t is missing because its not the type assignment); its the assignment to the equipment and i don\'t want to change the existing equipment_sport to equipment_sport_type).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS `' . $this->DatabasePrefix . 'equipment_spor` (
                `sportid` int(10) unsigned NOT NULL,
                `equipment_id` int(10) unsigned NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'equipment_spor`
            ADD PRIMARY KEY (`sportid`,`equipment_id`), ADD KEY `equipment_id` (`equipment_id`)');

        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'equipment_spor`
            ADD CONSTRAINT `' . $this->DatabasePrefix . 'equipment_spor_ibfk_1` FOREIGN KEY (`sportid`) REFERENCES `' . $this->DatabasePrefix . 'sport` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `' . $this->DatabasePrefix . 'equipment_spor_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `' . $this->DatabasePrefix . 'equipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `' . $this->DatabasePrefix . 'equipment_spor`');
    }
}
