<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20170715090403 extends AbstractMigration
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
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'sport` SET main_equipmenttypeid=NULL WHERE main_equipmenttypeid = 0');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'sport` s LEFT JOIN `' . $this->DatabasePrefix . 'equipment_type` t ON t.id=s.main_equipmenttypeid SET s.main_equipmenttypeid=NULL WHERE s.main_equipmenttypeid IS NOT NULL AND t.id IS NULL');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'sport` s LEFT JOIN `' . $this->DatabasePrefix . 'type` t ON t.id=s.default_typeid SET s.default_typeid=NULL WHERE s.default_typeid IS NOT NULL AND t.id IS NULL');
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'sport` ADD FOREIGN KEY (main_equipmenttypeid) REFERENCES `' . $this->DatabasePrefix . 'equipment_type` (id) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'sport` ADD FOREIGN KEY (default_typeid) REFERENCES `' . $this->DatabasePrefix . 'type` (id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
