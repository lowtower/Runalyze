<?php

/**
 * Bootstrap for PHPUnit
 * @author Hannes Christiansen
 * @package Runalyze\PHPUnit
 */
// ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__).'/../../../php/PEAR');

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

error_reporting(E_ALL);

if (! defined('RUNALYZE_TEST')) {
    define('RUNALYZE_TEST', true);
}

if (! defined('FRONTEND_PATH')) {
    define('FRONTEND_PATH', __DIR__ . '/../inc/');
}

define('TESTS_ROOT', __DIR__);

require dirname(__DIR__) . '/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '1';

// Load environment variables
(new Dotenv())->loadEnv(dirname(__DIR__) . '/.env');

// Boot Kernel, to get access to the container
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

require_once FRONTEND_PATH . 'system/define.consts.php';

$testTimezone = getenv('TEST_TIMEZONE') ?: 'Europe/Berlin';
putenv('TZ=' . $testTimezone);
date_default_timezone_set($testTimezone);

if (! defined('NL')) {
    define('NL', "\n");
}

if (! defined('NBSP')) {
    define('NBSP', '&nbsp;');
}

if (! defined('PREFIX')) {
    define('PREFIX', $container->getParameter('database_prefix.unittest'));
}

if (! defined('PERL_PATH')) {
    define('PERL_PATH', '/usr/bin/perl');
}

if (! defined('TTBIN_PATH')) {
    define('TTBIN_PATH', FRONTEND_PATH . '../call/perl/ttbincnv');
}

if (! defined('DATA_DIRECTORY')) {
    define('DATA_DIRECTORY', FRONTEND_PATH . '../data');
}

if (! defined('GEONAMES_USERNAME')) {
    define('GEONAMES_USERNAME', 'runalyze');
}

if (! defined('SQLITE_MOD_SPATIALITE')) {
    define('SQLITE_MOD_SPATIALITE', getenv('SQLITE_MOD_SPATIALITE') ?: 'mod_spatialite.so');
}

if (! defined('DAY_IN_S')) {
    define('DAY_IN_S', 86400);
}

$_SERVER['REQUEST_URI'] = '/runalyze/index.php';
$_SERVER['SCRIPT_NAME'] = '/runalyze/index.php';

// Read DB-Connection-Parameters from container instead of getenv()
$unittestConnection = $container->get('doctrine')
    ->getConnection('unittest');
$connectionParams = $unittestConnection->getParams();

DB::connect(
    $connectionParams['host'],
    $connectionParams['port'],
    $connectionParams['user'],
    $connectionParams['password'],
    $connectionParams['dbname']
);
DB::getInstance()->exec('SET SESSION sql_mode="TRADITIONAL"');
DB::getInstance()->exec('DELETE FROM `runalyze_account`');
DB::getInstance()->exec('INSERT INTO `runalyze_account` (`id`,`username`,`mail`) VALUES(1, "test", "test@test.com")');
DB::getInstance()->exec('INSERT INTO `runalyze_account` (`username`,`mail`) VALUES("zero", "zero@test.com")');
DB::getInstance()->exec('UPDATE `runalyze_account` SET `id`=0 WHERE `username`="zero"');

// Login
SessionAccountHandler::setAccount([
    'id' => 0,
    'username' => 'runalyze',
    'language' => 'de',
    'timezone' => '43',
    'mail' => 'noreply@runalyze.com',
]);

// Define RUNALYZE_TEST_TZ_LOOKUP
try {
    $lookup = new \Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup(TESTS_ROOT . '/../data/timezone.sqlite', SQLITE_MOD_SPATIALITE);
    $detectedTimezone = $lookup->getTimezoneForCoordinate(13.41, 52.52);

    define('RUNALYZE_TEST_TZ_LOOKUP', $detectedTimezone === 'Europe/Berlin');
} catch (\Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookupException $timezoneLookupException) {
    define('RUNALYZE_TEST_TZ_LOOKUP', false);
}

// Language functions
if (! function_exists('__')) {
    function __(string $text, $domain = 'runalyze'): string
    {
        return $text;
    }
}

if (! function_exists('_e')) {
    function _e(string $text, $domain = 'runalyze')
    {
        echo $text;
    }
}

if (! function_exists('_n')) {
    function _n(string $msg1, string $msg2, int $n, $domain = 'runalyze'): string
    {
        if ($n === 1) {
            return $msg1;
        }

        return $msg2;
    }
}

if (! function_exists('_ne')) {
    function _ne(string $msg1, string $msg2, int $n, $domain = 'runalyze')
    {
        if ($n === 1) {
            echo $msg1;
        }

        echo $msg2;
    }
}

// Clear cache
require_once FRONTEND_PATH . 'system/class.Cache.php';
new Cache();
Cache::clean();

// Load helper class
Helper::Unknown('');

// Load test helper
require_once FRONTEND_PATH . '../tests/fake/FakeContext.php';
