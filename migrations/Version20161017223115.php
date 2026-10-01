<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20161017223115 extends AbstractMigration
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
        return 'Delete session (if exists).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `' . $this->DatabasePrefix . 'sessions`');
    }

    public function down(Schema $schema): void
    {

    }
}
