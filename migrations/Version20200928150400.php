<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20200928150400 extends AbstractMigration
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
                                ADD `fit_lactate_threshold_hr` TINYINT(3) UNSIGNED DEFAULT NULL AFTER `fit_performance_condition_end`,
                                ADD `fit_total_ascent` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_lactate_threshold_hr`,
                                ADD `fit_total_descent` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_total_ascent`;
                                ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                DROP `fit_lactate_threshold_hr`,
                                DROP `fit_total_ascent`,
                                DROP `fit_total_descent`;
                                ');
    }
}
