<?php

declare(strict_types=1);

namespace Runalyze\Model\Account;

final class UserRoleTest extends \PHPUnit\Framework\TestCase
{
    public function testCheckRoleName(): void
    {
        $this->assertSame('ROLE_USER', UserRole::getRoleName(1));
        $this->assertSame('ROLE_ADMIN', UserRole::getRoleName(2));
    }
}
