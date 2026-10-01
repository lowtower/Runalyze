<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170430165002 extends AbstractMigration
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
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'notification` DROP foreign key `FK_F99B51889B6B5FBA`');
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'notification` ADD CONSTRAINT FK_F99B51889B6B5FBA FOREIGN KEY (account_id) REFERENCES `' . $this->DatabasePrefix . 'account` (id) ON DELETE CASCADE ON UPDATE CASCADE');

    }

    public function down(Schema $schema): void
    {
    }
}
