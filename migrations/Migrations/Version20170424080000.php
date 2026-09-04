<?php

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * cleanup sport table
 */
class Version20170424080000 extends AbstractMigration implements ContainerAwareInterface
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
        $prefix = $this->container->getParameter('app.database_prefix');
        $this->addSql('UPDATE `' . $prefix . 'sport` SET `main_equipmenttypeid` = NULL WHERE `main_equipmenttypeid`= 0');
        $this->addSql('UPDATE `' . $prefix . 'sport` SET `default_typeid` = NULL WHERE `default_typeid`= 0');
    }

    public function down(Schema $schema): void
    {

    }
}
