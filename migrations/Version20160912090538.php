<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20160912090538 extends AbstractMigration
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
        return 'Add first dev fit fields to trackdata.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'trackdata` ADD `smo2_0` longtext AFTER `groundcontact_balance`, 
                                ADD `smo2_1` longtext AFTER `smo2_0`, 
                                ADD `thb_0` longtext AFTER `smo2_1`, 
                                ADD `thb_1` longtext AFTER `thb_0`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'trackdata` DROP `smo2_0`, DROP `smo2_1`, DROP `thb_0`, DROP `thb_1`');
    }
}
