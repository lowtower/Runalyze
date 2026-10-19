<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\ContainsDisposableMailAddress;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\ContainsDisposableMailAddressValidator;
use Symfony\Component\Validator\Constraint;

final class ContainsDisposableMailAddressTest extends TestCase
{
    public function testDefaultMessageContainsValuePlaceholder(): void
    {
        $constraint = new ContainsDisposableMailAddress();

        $this->assertStringContainsString('{{ value }}', $constraint->message);
    }

    public function testIsInstanceOfSymfonyConstraint(): void
    {
        $constraint = new ContainsDisposableMailAddress();

        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    public function testValidatedByFollowsNamingConvention(): void
    {
        $constraint = new ContainsDisposableMailAddress();

        $this->assertSame(
            ContainsDisposableMailAddressValidator::class,
            $constraint->validatedBy()
        );
    }
}
