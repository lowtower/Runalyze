<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20161027173160 extends AbstractMigration
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
        return 'Database cleanup.';
    }

    public function up(Schema $schema): void
    {
        // Table: conf
        $this->addSql('DELETE FROM `' . $this->DatabasePrefix . 'equipment` WHERE `typeid` = 0');
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'equipment`
                  MODIFY `typeid` int(10) unsigned NOT NULL');

    }

    public function down(Schema $schema): void
    {

    }
}
