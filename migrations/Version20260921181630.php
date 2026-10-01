<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class Version20260921181630 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly string $DatabasePrefix
    ) {
        parent::__construct($connection, $logger);
    }

    public function getDescription() : string
    {
        return 'Make elevations_source nullable and set default to NULL.';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'route` CHANGE `elevations_source` `elevations_source` VARCHAR(255) NULL DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'route` CHANGE `elevations_source` `elevations_source` VARCHAR(255) NOT NULL DEFAULT \'\'');
    }
}
