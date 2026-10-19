<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\IsValidTimezone;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\IsValidTimezoneValidator;
use Runalyze\Parameter\Application\Timezone as TimezoneEnum;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class IsValidTimezoneValidatorTest extends TestCase
{
    /**
     * For tests that only need a context object to exist (no expectations
     * are ever put on it).
     *
     * @return array{0: IsValidTimezoneValidator, 1: ExecutionContextInterface&Stub}
     */
    private function createValidatorWithStubContext(): array
    {
        return [
            new IsValidTimezoneValidator(),
            $this->createStub(ExecutionContextInterface::class),
        ];
    }

    /**
     * For tests that put expects() on the context (e.g. buildViolation).
     *
     * @return array{0: IsValidTimezoneValidator, 1: ExecutionContextInterface&MockObject}
     */
    private function createValidatorWithMockContext(): array
    {
        return [
            new IsValidTimezoneValidator(),
            $this->createMock(ExecutionContextInterface::class),
        ];
    }

    #[DataProvider('provideValidTimezoneEnumValues')]
    public function testValidTimezoneEnumValueRaisesNoViolation(int $enumValue): void
    {
        [$validator, $context] = $this->createValidatorWithMockContext();
        $context->expects($this->never())
            ->method('buildViolation');

        $validator->validateInContext($enumValue, new IsValidTimezone(), $context);
    }

    /**
     * @return array<string, int[]>
     */
    public static function provideValidTimezoneEnumValues(): array
    {
        return [
            'UTC' => [TimezoneEnum::UTC],
            'Berlin' => [TimezoneEnum::BERLIN],
            'New York' => [TimezoneEnum::NEW_YORK],
            'Tokyo' => [TimezoneEnum::TOKYO],
        ];
    }

    #[DataProvider('provideInvalidTimezoneValues')]
    public function testInvalidValueRaisesViolation(mixed $value): void
    {
        [$validator, $context] = $this->createValidatorWithMockContext();
        $constraint = new IsValidTimezone();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', (string) $value)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('addViolation');

        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $validator->validateInContext($value, $constraint, $context);
    }

    /**
     * @return array<string, int[]|string[]>
     */
    public static function provideInvalidTimezoneValues(): array
    {
        return [
            'out-of-range integer' => [999999],
            'negative integer' => [-1],
            'IANA string (not a valid enum int)' => ['Europe/Berlin'],
            'empty string' => [''],
        ];
    }

    public function testWrongConstraintTypeIsSilentlyIgnoredWhenValueIsValid(): void
    {
        [$validator, $context] = $this->createValidatorWithMockContext();
        $context->expects($this->never())
            ->method('buildViolation');

        $validator->validateInContext(TimezoneEnum::BERLIN, new NotBlank(), $context);
    }

    public function testWrongConstraintTypeThrowsOnlyWhenValueIsAlsoInvalid(): void
    {
        [$validator, $context] = $this->createValidatorWithStubContext();

        $this->expectException(\InvalidArgumentException::class);

        $validator->validateInContext(999999, new NotBlank(), $context);
    }
}
