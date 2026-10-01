<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20161123211900 extends AbstractMigration
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
        return 'Set `training.routeid` nullable.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'training` MODIFY `routeid` int(10) unsigned DEFAULT NULL');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'training` SET `routeid` = NULL WHERE `routeid`= 0');
    }

    public function down(Schema $schema): void
    {

    }
}
