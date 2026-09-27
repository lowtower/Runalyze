<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\System;

use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
class DBTest extends TestCase
{
    public function testConnectDoesNotReplaceAnAlreadyEstablishedConnection(): void
    {
        \DB::connect('this-host-does-not-exist.invalid', '3306', 'invalid-user', 'invalid-password', 'invalid-database');

        $account = \DB::getInstance()->fetchByID('runalyze_account', 0);

        $this->assertSame(0, (int) $account['id']);
    }
}
