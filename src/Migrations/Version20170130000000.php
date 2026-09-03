<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * bernard mysql schema without having bernard/bernard-bundle
 */
class Version20170130000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS `bernard_messages` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `queue` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `message` longtext COLLATE utf8_unicode_ci NOT NULL,
                `visible` tinyint(1) NOT NULL DEFAULT \'1\',
                `sentAt` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `IDX_28999D87FFD7F635BADAD2C7AB0E859` (`queue`,`sentAt`,`visible`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ');

        $this->addSql('
            CREATE TABLE IF NOT EXISTS `bernard_queues` (
                `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bernard_messages');
        $this->addSql('DROP TABLE bernard_queues');
    }
}
