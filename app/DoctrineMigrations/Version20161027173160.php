<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * database cleanup
 */
class Version20161027173160 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        //Table: conf
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('DELETE FROM `' . $prefix . 'equipment` WHERE `typeid` = 0');
        $this->addSql('ALTER TABLE `' . $prefix . 'equipment`
                  MODIFY `typeid` int(10) unsigned NOT NULL');

    }

    public function down(Schema $schema): void
    {

    }
}
