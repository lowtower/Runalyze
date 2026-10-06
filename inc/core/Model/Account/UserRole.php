<?php

declare(strict_types=1);

namespace Runalyze\Model\Account;

use Runalyze\Common\Enum\AbstractEnum;

final class UserRole extends AbstractEnum
{
    public const int ROLE_USER = 1;

    public const int ROLE_ADMIN = 2;

    /**
     * @param int $roleId int from internal enum
     * @throws \InvalidArgumentException
     */
    public static function getRoleName(int $roleId): string
    {
        $roleNames = self::roleNamesArray();

        if (! isset($roleNames[$roleId])) {
            throw new \InvalidArgumentException('Invalid role id "' . $roleId . '".');
        }

        return $roleNames[$roleId];
    }

    /**
     * @return array<int, string> [int(enum) => 'role name']
     */
    private static function roleNamesArray(): array
    {
        return [
            self::ROLE_USER => 'ROLE_USER',
            self::ROLE_ADMIN => 'ROLE_ADMIN',
        ];
    }
}
