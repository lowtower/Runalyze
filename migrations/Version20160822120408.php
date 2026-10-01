<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20160822120408 extends AbstractMigration
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
        return 'Drop column lastlogin.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'account` DROP `lastlogin`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'account` ADD `lastlogin` int(11) NOT NULL DEFAULT 0');
    }
}
