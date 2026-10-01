<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20171217215801 extends AbstractMigration
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
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'sport` SET default_privacy=1 WHERE accountid IN  (select accountid from `' . $this->DatabasePrefix . "conf` WHERE `key`='TRAINING_MAKE_PUBLIC' and value='false')");
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'sport` SET default_privacy=0 WHERE accountid IN  (select accountid from `' . $this->DatabasePrefix . "conf` WHERE `key`='TRAINING_MAKE_PUBLIC' and value='true')");
        $this->addSql('DELETE FROM `' . $this->DatabasePrefix . "conf` WHERE  `key`='TRAINING_MAKE_PUBLIC'");
    }

    public function down(Schema $schema): void
    {
        $this->DatabasePrefix;

    }
}
