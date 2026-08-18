<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * remove map color option
 */
class Version20171103195530 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('DELETE FROM `'.$prefix."conf` WHERE `key`='TRAINING_MAP_COLOR'");
    }

    public function down(Schema $schema): void
    {
    }
}
