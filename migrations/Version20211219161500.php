<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20211219161500 extends AbstractMigration
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
                                ADD `fit_self_evaluation_feeling` TINYINT(3) UNSIGNED DEFAULT NULL AFTER `fit_total_descent`,
                                ADD `fit_self_evaluation_perceived_effort` TINYINT(3) UNSIGNED DEFAULT NULL AFTER `fit_self_evaluation_feeling`,
                                ADD `avg_respiration_rate` TINYINT(2) UNSIGNED DEFAULT NULL AFTER `fit_self_evaluation_perceived_effort`,
                                ADD `max_respiration_rate` TINYINT(2) UNSIGNED DEFAULT NULL AFTER `avg_respiration_rate`;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training`
                                DROP `fit_self_evaluation_feeling`,
                                DROP `fit_self_evaluation_perceived_effort`,
                                DROP `avg_respiration_rate`,
                                DROP `max_respiration_rate`;');
    }
}
