<?php

declare(strict_types=1);

use DoctrineExtensions\Query\Mysql\FromUnixtime;
use DoctrineExtensions\Query\Mysql\Month;
use DoctrineExtensions\Query\Mysql\Round;
use DoctrineExtensions\Query\Mysql\Week;
use DoctrineExtensions\Query\Mysql\Year;
use Runalyze\Bundle\CoreBundle\Doctrine\Hydrator\ColumnHydrator;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\CastedDecimalScale1Type;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\CastedDecimalScale2Type;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\GeohashArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArrayString;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzePauseArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzeRoundArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\TinyIntType;
use Runalyze\Bundle\CoreBundle\Tests\Doctrine\DQL\Sqlite\SqliteFromUnixtime;
use Runalyze\Bundle\CoreBundle\Tests\Doctrine\DQL\Sqlite\SqliteMonth;
use Runalyze\Bundle\CoreBundle\Tests\Doctrine\DQL\Sqlite\SqliteYear;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'default_connection' => 'default',
            'connections' => [
                'default' => [
                    'driver' => 'pdo_mysql',
                    'host' => '%env(DATABASE_HOST)%',
                    'port' => '%env(int:DATABASE_PORT)%',
                    'dbname' => '%env(DATABASE_NAME)%',
                    'user' => '%env(DATABASE_USER)%',
                    'password' => '%env(DATABASE_PASSWORD)%',
                    'server_version' => '%env(DATABASE_SERVER_VERSION)%',
                    'charset' => 'UTF8',
                    'mapping_types' => [
                        'enum' => 'string',
                    ],
                    'profiling_collect_backtrace' => '%kernel.debug%',
                ],
            ],
            'types' => [
                'casted_decimal_1' => CastedDecimalScale1Type::class,
                'casted_decimal_2' => CastedDecimalScale2Type::class,
                'tinyint' => TinyIntType::class,
                'pipe_array' => PipeDelimitedArray::class,
                'pipe_array_str' => PipeDelimitedArrayString::class,
                'geohash_array' => GeohashArray::class,
                'runalyze_pause_array' => RunalyzePauseArray::class,
                'runalyze_round_array' => RunalyzeRoundArray::class,
            ],
        ],
        'orm' => [
            'validate_xml_mapping' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping' => false,
            'mappings' => [
                'CoreBundle' => [
                    'type' => 'attribute',
                    'is_bundle' => false,
                    'dir' => '%kernel.project_dir%/src/CoreBundle/Entity',
                    'prefix' => 'Runalyze\Bundle\CoreBundle\Entity',
                    'alias' => 'CoreBundle',
                ],
            ],
            'resolve_target_entities' => null,
            'metadata_cache_driver' => [
                'type' => 'pool',
                'pool' => 'doctrine.system_cache_pool',
            ],
            'query_cache_driver' => [
                'type' => 'pool',
                'pool' => 'doctrine.system_cache_pool',
            ],
            'result_cache_driver' => [
                'type' => 'pool',
                'pool' => 'doctrine.result_cache_pool',
            ],
            'hydrators' => [
                'COLUMN_HYDRATOR' => ColumnHydrator::class,
            ],
            'dql' => [
                'datetime_functions' => [
                    'from_unixtime' => FromUnixtime::class,
                    'month' => Month::class,
                    'week' => Week::class,
                    'year' => Year::class,
                ],
                'numeric_functions' => [
                    'round' => Round::class,
                ],
            ],
        ],
    ]);

    $containerConfigurator->extension('framework', [
        'cache' => [
            'pools' => [
                'doctrine.result_cache_pool' => [
                    'adapter' => 'cache.app',
                ],
                'doctrine.system_cache_pool' => [
                    'adapter' => 'cache.system',
                ],
            ],
        ],
    ]);
    if ($containerConfigurator->env() === 'prod') {
        $containerConfigurator->extension('doctrine', [
            'dbal' => [
                'default_connection' => 'default',
                'connections' => [
                    'default' => [
                        'driver' => 'pdo_mysql',
                        'host' => '%env(DATABASE_HOST)%',
                        'port' => '%env(int:DATABASE_PORT)%',
                        'dbname' => '%env(DATABASE_NAME)%',
                        'user' => '%env(DATABASE_USER)%',
                        'password' => '%env(DATABASE_PASSWORD)%',
                        'server_version' => '%env(DATABASE_SERVER_VERSION)%',
                        'charset' => 'UTF8',
                        'options' => [
                            1002 => "SET time_zone = '+00:00'",
                        ],
                        'mapping_types' => [
                            'enum' => 'string',
                        ],
                    ],
                ],
            ],
            'orm' => [
                'query_cache_driver' => [
                    'type' => 'pool',
                    'pool' => 'doctrine.system_cache_pool',
                ],
                'result_cache_driver' => [
                    'type' => 'pool',
                    'pool' => 'doctrine.result_cache_pool',
                ],
            ],
        ]);
        $containerConfigurator->extension('framework', [
            'cache' => [
                'pools' => [
                    'doctrine.result_cache_pool' => [
                        'adapter' => 'cache.app',
                    ],
                    'doctrine.system_cache_pool' => [
                        'adapter' => 'cache.system',
                    ],
                ],
            ],
        ]);
    }

    if ($containerConfigurator->env() === 'dev') {
        $containerConfigurator->extension('doctrine', [
            'dbal' => [
                'connections' => [
                    'default' => [
                        'profiling_collect_backtrace' => true,
                    ],
                ],
            ],
        ]);
    }

    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('doctrine', [
            'dbal' => [
                'default_connection' => 'default',
                'connections' => [
                    'default' => [
                        'driver' => 'pdo_sqlite',
                        'path' => '%env(resolve:TEST_SQLITE_PATH)%',
                    ],
                    'unittest' => [
                        'driver' => 'pdo_mysql',
                        'host' => '%env(DB_TEST_HOST)%',
                        'port' => '%env(int:DB_TEST_PORT)%',
                        'dbname' => '%env(DB_UNITTEST_NAME)%',
                        'user' => '%env(DB_TEST_USER)%',
                        'password' => '%env(DB_TEST_PASSWORD)%',
                        'charset' => 'UTF8',
                        'options' => [
                            1002 => "SET time_zone = '+00:00'",
                        ],
                    ],
                ],
            ],
            'orm' => [
                'dql' => [
                    'datetime_functions' => [
                        'from_unixtime' => SqliteFromUnixtime::class,
                        'year' => SqliteYear::class,
                        'month' => SqliteMonth::class,
                    ],
                ],
            ],
        ]);
        $containerConfigurator->services()
            ->defaults()
            ->tag('doctrine.fixture.orm')
            ->load(
                'Runalyze\\Bundle\\CoreBundle\\Tests\\DataFixtures\\ORM\\',
                '%kernel.project_dir%/tests/CoreBundle/DataFixtures/ORM/*'
            );
    }

    if ($containerConfigurator->env() === 'test_empty') {
        $containerConfigurator->extension('doctrine', [
            'dbal' => [
                'default_connection' => 'default',
                'connections' => [
                    'default' => [
                        'driver' => 'pdo_mysql',
                        'host' => '%env(DB_TEST_HOST)%',
                        'port' => '%env(int:DB_TEST_PORT)%',
                        'dbname' => '%env(DB_TEST_EMPTY_NAME)%',
                        'user' => '%env(DB_TEST_USER)%',
                        'password' => '%env(DB_TEST_PASSWORD)%',
                        'charset' => 'UTF8',
                        'options' => [
                            1002 => "SET time_zone = '+00:00'",
                        ],
                    ],
                ],
            ],
        ]);
    }
};
