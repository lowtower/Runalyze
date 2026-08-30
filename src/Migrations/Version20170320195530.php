<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * remove configuration option training show after create
 */
class Version20170320195530 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('DELETE FROM `' . $prefix . "conf` WHERE `key`='TRAINING_SHOW_AFTER_CREATE'");
    }

    public function down(Schema $schema): void
    {
    }
}
