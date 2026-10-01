<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Version20160830124637 extends AbstractMigration
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
        return 'Add birthyear and gender to account.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `' . $this->DatabasePrefix . 'account` ADD `gender` tinyint(1) unsigned NOT NULL DEFAULT 0, ADD `birthyear` int(4) unsigned DEFAULT NULL');
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'account` a INNER JOIN `' . $this->DatabasePrefix . "conf` c ON a.id = c.accountid SET a.gender = 1 WHERE c.key='GENDER' AND c.accountid > 0 AND c.value='m'");
        $this->addSql('UPDATE `' . $this->DatabasePrefix . 'account` a INNER JOIN `' . $this->DatabasePrefix . "conf` c ON a.id = c.accountid SET a.gender = 2 WHERE c.key='GENDER' AND c.accountid > 0 AND c.value='f'");
        $this->addSql('DELETE FROM `' . $this->DatabasePrefix . "conf` WHERE `key`='GENDER'");
    }

    public function down(Schema $schema): void
    {
    }
}
