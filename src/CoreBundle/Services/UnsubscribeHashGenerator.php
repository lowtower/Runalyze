<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

class UnsubscribeHashGenerator
{
    public function __construct(
        private readonly string $appSecret
    ) {}

    public function generate(string $username): string
    {
        return hash_hmac('sha256', $username, $this->appSecret);
    }

    public function isValid(string $username, string $hash): bool
    {
        return hash_equals($this->generate($username), $hash);
    }
}
