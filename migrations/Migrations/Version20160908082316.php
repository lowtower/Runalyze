<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * remove autologin_hash
 */
class Version20160908082316 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');
        $this->addSql('ALTER TABLE `' . $prefix . 'account` DROP `autologin_hash`');
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->container->getParameter('app.database_prefix');
        $this->addSql('ALTER TABLE `' . $prefix . "account` ADD `autologin_hash` varchar(32) NOT NULL DEFAULT '' AFTER lastaction");
    }
}
