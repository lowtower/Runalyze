<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20231115200000 extends AbstractMigration
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
        return 'TSC: Creates new column for Pace-Pro additional informations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                ADD `pace_goal` JSON DEFAULT NULL AFTER `partner`,
                                ADD `fit_run_time` MEDIUMINT UNSIGNED DEFAULT NULL AFTER `fit_load_peak`,
                                ADD `fit_walk_time` MEDIUMINT UNSIGNED DEFAULT NULL AFTER `fit_run_time`,
                                ADD `fit_stand_time` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_walk_time`;
                                ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                DROP `pace_goal`,
                                DROP `fit_run_time`,
                                DROP `fit_walk_time`,
                                DROP `fit_stand_time`;
                                ');
    }
}
