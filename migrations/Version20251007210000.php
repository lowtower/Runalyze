<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20251007210000 extends AbstractMigration
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
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                ADD `with_goal` TINYINT(1) UNSIGNED DEFAULT "0" AFTER `is_track`,
                                ADD `kcal_rest` SMALLINT UNSIGNED DEFAULT NULL AFTER `kcal`,
                                ADD `fit_training_effect_benefit` TINYINT(1) UNSIGNED DEFAULT NULL AFTER `fit_hrv_analysis`,
                                ADD `fit_sweat_loss` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_seconds_hr_zones`,
                                ADD `fit_lactate_threshold_pace_ms` DECIMAL(4,2) UNSIGNED DEFAULT NULL AFTER `fit_lactate_threshold_hr`;');
        // set the new with_target column with the values from the old data
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'training` SET `with_goal`="1" WHERE `splits_additional` like "%FIT target%" or `pace_goal` is not null;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                DROP `with_goal`,
                                DROP `kcal_rest`,
                                DROP `fit_training_effect_benefit`,
                                DROP `fit_sweat_loss`,
                                DROP `fit_lactate_threshold_pace_ms`;');
    }
}
