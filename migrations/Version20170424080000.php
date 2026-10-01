<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170424080000 extends AbstractMigration
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
        return 'Cleanup sport table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'sport` SET `main_equipmenttypeid` = NULL WHERE `main_equipmenttypeid`= 0');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'sport` SET `default_typeid` = NULL WHERE `default_typeid`= 0');
    }

    public function down(Schema $schema): void
    {

    }
}
