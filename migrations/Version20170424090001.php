<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170424090001 extends AbstractMigration
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
        return 'Unique key on dataset.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER table `' . $this->DatabasePrefix . 'dataset` DROP INDEX `unique_key`');
    }

    public function down(Schema $schema): void
    {
        // not possible
    }
}
