<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170320195530 extends AbstractMigration
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
        return 'Remove configuration option training show after create.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM `' . $this->DatabasePrefix . "conf` WHERE `key`='TRAINING_SHOW_AFTER_CREATE'");
    }

    public function down(Schema $schema): void
    {
    }
}
