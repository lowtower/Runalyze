<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20171225193117 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly string $DatabasePrefix
    ) {
        parent::__construct($connection, $logger);
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'raceresult` CHANGE `official_distance` `official_distance` DECIMAL(6,2) NULL DEFAULT NULL');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'raceresult` SET `official_distance` = NULL WHERE `official_distance` = 0.0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'raceresult` SET `official_distance` = 0.0 WHERE `official_distance` IS NULL');
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'raceresult` CHANGE `official_distance` `official_distance` DECIMAL(6,2) NOT NULL');
    }
}
