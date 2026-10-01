<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170614214532 extends AbstractMigration
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
        return 'Add percentage_hilly and climb_score to training and set lock on route.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training` ADD `percentage_hilly` decimal(3,2) unsigned DEFAULT NULL AFTER `elevation`, ADD `climb_score` decimal(3,1) unsigned DEFAULT NULL AFTER `elevation`');

        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'route` SET `lock` = 1 WHERE `elevations_original` IS NOT NULL OR `elevations_corrected` IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training` DROP COLUMN `percentage_hilly`, DROP COLUMN `climb_score`');
    }
}
