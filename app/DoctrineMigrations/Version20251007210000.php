<?php

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20251007210000 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface|null */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');

        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                ADD `with_goal` TINYINT(1) UNSIGNED DEFAULT "0" AFTER `is_track`,
                                ADD `kcal_rest` SMALLINT UNSIGNED DEFAULT NULL AFTER `kcal`,
                                ADD `fit_training_effect_benefit` TINYINT(1) UNSIGNED DEFAULT NULL AFTER `fit_hrv_analysis`,
                                ADD `fit_sweat_loss` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_seconds_hr_zones`,
                                ADD `fit_lactate_threshold_pace_ms` DECIMAL(4,2) UNSIGNED DEFAULT NULL AFTER `fit_lactate_threshold_hr`;');
        // set the new with_target column with the values from the old data
        $this->addSql('UPDATE `'.$prefix.'training` SET `with_goal`="1" WHERE `splits_additional` like "%FIT target%" or `pace_goal` is not null;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                DROP `with_goal`,
                                DROP `kcal_rest`,
                                DROP `fit_training_effect_benefit`,
                                DROP `fit_sweat_loss`,
                                DROP `fit_lactate_threshold_pace_ms`;');
    }
}
