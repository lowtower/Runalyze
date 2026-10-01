<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20240516200000 extends AbstractMigration
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
        return 'TSC: Creates new column for HR user-max and zones informations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                ADD `max_hr_user` TINYINT UNSIGNED DEFAULT NULL AFTER `pulse_avg_active`,
                                ADD `hr_zone_bounds` VARCHAR(200) DEFAULT NULL AFTER `max_hr_user`,
                                ADD `fit_seconds_hr_zones` VARCHAR(100) DEFAULT NULL AFTER `fit_stand_time`;
                                ');
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'sport`
                                ADD `hr_zone_bounds` VARCHAR(200) DEFAULT NULL AFTER `default_privacy`;
                                ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                DROP `max_hr_user`,
                                DROP `hr_zone_bounds`,
                                DROP `fit_seconds_hr_zones`;
                                ');
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'sport`
                                DROP `hr_zone_bounds`;
                                ');
    }
}
