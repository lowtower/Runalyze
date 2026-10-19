<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\IsValidTimezone;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\IsValidTimezoneValidator;
use Symfony\Component\Validator\Constraint;

final class IsValidTimezoneTest extends TestCase
{
    public function testDefaultMessageContainsValuePlaceholder(): void
    {
        $constraint = new IsValidTimezone();

        $this->assertStringContainsString('{{ value }}', $constraint->message);
    }

    public function testIsInstanceOfSymfonyConstraint(): void
    {
        $constraint = new IsValidTimezone();

        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    public function testValidatedByFollowsNamingConvention(): void
    {
        $constraint = new IsValidTimezone();

        $this->assertSame(
            IsValidTimezoneValidator::class,
            $constraint->validatedBy()
        );
    }
}
