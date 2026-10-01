<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20171104230413 extends AbstractMigration
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
        return 'Add lock to trackdata.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'trackdata` ADD `lock` tinyint(1) unsigned NOT NULL DEFAULT 0');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'trackdata` SET `lock` = 1 WHERE `distance` IS NOT NULL OR `cadence` IS NOT NULL OR `heartrate` IS NOT NULL');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'route` SET `lock` = 1 WHERE `elevations_corrected` IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'trackdata` DROP `lock`');
    }
}
