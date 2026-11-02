<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Re-applies the test timezone before every single test.
 *
 * Some application code mutates the global default timezone, most notably
 * UserTimezoneListener::onKernelRequest(), which calls date_default_timezone_set()
 * with the authenticated account's timezone during kernel tests. As test accounts
 * default to Timezone::UTC, every test that runs after such a kernel test would
 * otherwise be executed with UTC instead of the intended TEST_TIMEZONE as default
 * timezone.
 */
final class ForceTestTimezoneExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class() implements PreparationStartedSubscriber {
            public function notify(PreparationStarted $event): void
            {
                $timezone = getenv('TEST_TIMEZONE') ?: 'Europe/Berlin';

                putenv('TZ=' . $timezone);
                date_default_timezone_set($timezone);
            }
        });
    }
}
