<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170423080527 extends AbstractMigration
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
        return 'Add fit_performance_condition_end to training.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training` ADD `fit_performance_condition_end` tinyint(3) unsigned DEFAULT NULL AFTER `fit_performance_condition`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training` DROP COLUMN `fit_performance_condition_end`');
    }
}
