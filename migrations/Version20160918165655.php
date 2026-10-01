<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20160918165655 extends AbstractMigration
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
        return 'Rectify sport column main_equipmenttypeid.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'sport` CHANGE `main_equipmenttypeid` `main_equipmenttypeid` int(10) unsigned DEFAULT NULL, CHANGE `accountid` `accountid` int(11) unsigned NOT NULL');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'sport` SET `main_equipmenttypeid` = NULL WHERE `main_equipmenttypeid`= 0');
    }

    public function down(Schema $schema): void
    {

    }
}
