<?php

/**
 * Bootstrap for PHPUnit
 * @author Hannes Christiansen
 * @package Runalyze\PHPUnit
 */
//ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__).'/../../../php/PEAR');

error_reporting(E_ALL);

if (! defined('RUNALYZE_TEST'))
	define('RUNALYZE_TEST', true);

if (! defined('FRONTEND_PATH'))
	define('FRONTEND_PATH', dirname(__FILE__).'/../inc/');

define('TESTS_ROOT', __DIR__);

require_once FRONTEND_PATH.'../app/autoload.php';

require_once FRONTEND_PATH.'system/define.consts.php';

$testTimezone = getenv('TEST_TIMEZONE') ?: 'Europe/Berlin';
putenv('TZ='.$testTimezone);
date_default_timezone_set($testTimezone);

if (! defined('NL'))
	define('NL', "\n");

if (! defined('NBSP'))
	define('NBSP', '&nbsp;');

if (! defined('PREFIX'))
	define('PREFIX', 'runalyze_');

if (! defined('PERL_PATH'))
	define('PERL_PATH', '/usr/bin/perl');

if (! defined('TTBIN_PATH'))
	define('TTBIN_PATH', FRONTEND_PATH.'../call/perl/ttbincnv');

if (! defined('DATA_DIRECTORY'))
    define('DATA_DIRECTORY', FRONTEND_PATH.'../data');

if (! defined('GEONAMES_USERNAME'))
	define('GEONAMES_USERNAME', 'runalyze');

if (! defined('SQLITE_MOD_SPATIALITE'))
	define('SQLITE_MOD_SPATIALITE', getenv('SQLITE_MOD_SPATIALITE') ?: 'mod_spatialite.so');

if (! defined('DAY_IN_S'))
	define('DAY_IN_S', 86400);

$_SERVER['REQUEST_URI'] = '/runalyze/index.php';
$_SERVER['SCRIPT_NAME'] = '/runalyze/index.php';

// Load and clean database
$databaseHost = getenv('DB_TEST_HOST') ?: getenv('DB_HOST') ?: 'db';
$databasePort = getenv('DB_TEST_PORT') ?: getenv('DB_PORT') ?: '3306';
$databaseUser = getenv('DB_TEST_USER') ?: 'runalyze_test';
$databasePassword = getenv('DB_TEST_PASSWORD') ?: 'password';
if ($databasePassword === false) {
    $databasePassword = 'password';
}

$databaseName = getenv('DB_UNITTEST_NAME') ?: 'runalyze_unittest';
DB::connect(
    $databaseHost,
    $databasePort,
    $databaseUser,
    $databasePassword,
    $databaseName
);
DB::getInstance()->exec('SET SESSION sql_mode="TRADITIONAL"');
DB::getInstance()->exec('DELETE FROM `runalyze_account`');
DB::getInstance()->exec('INSERT INTO `runalyze_account` (`id`,`username`,`mail`) VALUES(1, "test", "test@test.com")');
DB::getInstance()->exec('INSERT INTO `runalyze_account` (`username`,`mail`) VALUES("zero", "zero@test.com")');
DB::getInstance()->exec('UPDATE `runalyze_account` SET `id`=0 WHERE `username`="zero"');

// Login
SessionAccountHandler::setAccount(array(
    'id' => 0,
    'username' => 'runalyze',
    'language' => 'de',
    'timezone' => '43',
    'mail' => 'noreply@runalyze.com',
));
// Define RUNALYZE_TEST_TZ_LOOKUP
try {
    $lookup = new \Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup(TESTS_ROOT.'/../data/timezone.sqlite', SQLITE_MOD_SPATIALITE);
    $detectedTimezone = $lookup->getTimezoneForCoordinate(13.41, 52.52);

	define('RUNALYZE_TEST_TZ_LOOKUP', $detectedTimezone === 'Europe/Berlin');
} catch (\Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookupException $timezoneLookupException) {
	define('RUNALYZE_TEST_TZ_LOOKUP', false);
}

// Language functions
if (! function_exists('__')) {
	function __($text, $domain = 'runalyze') {
		return $text;
	}
}

if (! function_exists('_e')) {
	function _e($text, $domain = 'runalyze') {
		echo $text;
	}
}

if (! function_exists('_n')) {
	function _n($msg1, $msg2, $n, $domain = 'runalyze') {
		if ($n == 1)
			return $msg1;

		return $msg2;
	}
}

if (! function_exists('_ne')) {
	function _ne($msg1, $msg2, $n, $domain = 'runalyze') {
		if ($n == 1)
			echo $msg1;

		echo $msg2;
	}
}

// Clear cache
require_once FRONTEND_PATH.'system/class.Cache.php';
new Cache();
Cache::clean();

// Load helper class
Helper::Unknown('');

// Load test helper
require_once FRONTEND_PATH.'../tests/fake/FakeContext.php';

// Add doctrine types (required for test cases that do not use the kernel)
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\TinyIntType::TINYINT, \Runalyze\Bundle\CoreBundle\Doctrine\Types\TinyIntType::class);
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray::PIPE_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray::class);
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArrayString::PIPE_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArrayString::class);
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\GeohashArray::GEOHASH_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\GeohashArray::class);
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzePauseArray::RUNALYZE_PAUSE_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzePauseArray::class);
\Doctrine\DBAL\Types\Type::addType(\Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzeRoundArray::RUNALYZE_ROUND_ARRAY, \Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzeRoundArray::class);
