<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170320194525 extends AbstractMigration
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
        $this->addSql('CREATE TABLE IF NOT EXISTS `' . $this->DatabasePrefix . 'notification` (
                          `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
                          `messageType`  tinyint unsigned NOT NULL,
                          `createdAt` INT UNSIGNED NOT NULL,
                          `expirationAt` INT UNSIGNED DEFAULT NULL,
                          `data` TINYTEXT NOT NULL,
                          `wasRead` tinyint(1) unsigned NOT NULL DEFAULT 0,
                          `account_id` INT UNSIGNED NOT NULL,
                          INDEX IDX_F99B51889B6B5FBA (account_id),
                          PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
                        ');
        $this->addSql('ALTER TABLE ' . $this->DatabasePrefix . 'notification ADD CONSTRAINT FK_F99B51889B6B5FBA FOREIGN KEY (account_id) REFERENCES ' . $this->DatabasePrefix . 'account (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `' . $this->DatabasePrefix . 'notification`');
    }
}
