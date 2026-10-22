<?php

declare(strict_types=1);

namespace Runalyze\Tests\Bundle\CoreBundle\Services;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\DisposableDomainProvider;

final class DisposableDomainProviderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/disposable_provider_' . uniqid();

        mkdir($this->tempDir . '/var/email', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
    }

    public function testLoadsDomainsFromFile(): void
    {
        file_put_contents(
            $this->tempDir . '/var/email/disposable-domains.txt',
            "mailinator.com\n"
        );

        $provider = new DisposableDomainProvider($this->tempDir);
        $domains = $provider->getDomains();

        $this->assertArrayHasKey('mailinator.com', $domains);
    }

    public function testFallbackWhenFileMissing(): void
    {
        $provider = new DisposableDomainProvider($this->tempDir);
        $domains = $provider->getDomains();

        $this->assertArrayHasKey('mailinator.com', $domains);
    }

    private function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
