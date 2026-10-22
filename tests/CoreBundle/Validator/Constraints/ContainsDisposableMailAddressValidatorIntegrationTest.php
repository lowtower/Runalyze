<?php

declare(strict_types=1);

namespace Runalyze\Tests\Bundle\CoreBundle\Validator\Constraints;

use PHPUnit\Framework\Attributes\Test;
use Runalyze\Bundle\CoreBundle\Services\DisposableDomainProvider;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\ContainsDisposableMailAddress;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\ContainsDisposableMailAddressValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class ContainsDisposableMailAddressValidatorIntegrationTest extends ConstraintValidatorTestCase
{
    private ?string $tempDir = null;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/disposable_test_' . uniqid();
        if (! is_dir($this->tempDir . '/var/email')) {
            mkdir($this->tempDir . '/var/email', 0777, true);
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);

        parent::tearDown();
    }

    #[Test]
    public function testRejectsDisposableEmailFromFile(): void
    {
        file_put_contents(
            $this->tempDir . '/var/email/disposable-domains.txt',
            "mailinator.com\n"
        );

        $this->validator->validateInContext('test@mailinator.com', new ContainsDisposableMailAddress(), $this->context);

        $this->buildViolation('Disposable email addresses are not permitted: "{{ value }}"')
            ->setParameter('{{ value }}', 'test@mailinator.com')
            ->assertRaised();
    }

    #[Test]
    public function testAcceptsNonDisposableEmailFromFile(): void
    {
        file_put_contents(
            $this->tempDir . '/var/email/disposable-domains.txt',
            "mailinator.com\n"
        );

        $this->validator->validateInContext('user@gmail.com', new ContainsDisposableMailAddress(), $this->context);

        $this->assertNoViolation();
    }

    #[Test]
    public function testUsesFallbackWhenFileMissing(): void
    {
        $this->validator->validateInContext('test@mailinator.com', new ContainsDisposableMailAddress(), $this->context);

        $this->buildViolation('Disposable email addresses are not permitted: "{{ value }}"')
            ->setParameter('{{ value }}', 'test@mailinator.com')
            ->assertRaised();
    }

    #[Test]
    public function testUsesFallbackWhenFileEmpty(): void
    {
        file_put_contents(
            $this->tempDir . '/var/email/disposable-domains.txt',
            ''
        );

        $this->validator->validateInContext('test@mailinator.com', new ContainsDisposableMailAddress(), $this->context);

        $this->buildViolation('Disposable email addresses are not permitted: "{{ value }}"')
            ->setParameter('{{ value }}', 'test@mailinator.com')
            ->assertRaised();
    }

    protected function createValidator(): ContainsDisposableMailAddressValidator
    {
        $provider = new DisposableDomainProvider($this->tempDir);

        return new ContainsDisposableMailAddressValidator($provider);
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
