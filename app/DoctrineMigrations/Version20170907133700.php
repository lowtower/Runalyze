<?php

declare(strict_types=1);

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * remove search results per page conf
 */
class Version20170907133700 extends AbstractMigration implements ContainerAwareInterface
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
        $this->addSql('DELETE FROM `'.$prefix."conf` WHERE `key`='SEARCH_RESULTS_PER_PAGE'");
    }

    public function down(Schema $schema): void
    {
    }
}
