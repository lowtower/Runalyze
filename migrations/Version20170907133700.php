<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170907133700 extends AbstractMigration
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
        return 'Remove search results per page conf.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM `' . $this->DatabasePrefix . "conf` WHERE `key`='SEARCH_RESULTS_PER_PAGE'");
    }

    public function down(Schema $schema): void
    {
    }
}
