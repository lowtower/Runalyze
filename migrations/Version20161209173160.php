<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20161209173160 extends AbstractMigration
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
        return 'Route lock.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'route`
                  ADD `lock` tinyint(1) unsigned NOT NULL DEFAULT 0');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'route`
                  SET `lock` = 1 WHERE geohashes != \'\'');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'route` DROP `lock`');
    }
}
