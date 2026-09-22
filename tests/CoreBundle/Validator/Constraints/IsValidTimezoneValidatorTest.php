<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\IsValidTimezone;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\IsValidTimezoneValidator;
use Runalyze\Parameter\Application\Timezone as TimezoneEnum;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class IsValidTimezoneValidatorTest extends TestCase
{
    /**
     * @return array<int, \Runalyze\Bundle\CoreBundle\Validator\Constraints\IsValidTimezoneValidator|(\PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\Validator\Context\ExecutionContextInterface)>
     */
    private function createValidatorWithContext(): array
    {
        $validator = new IsValidTimezoneValidator();
        $context = $this->createMock(ExecutionContextInterface::class);
        $validator->initialize($context);

        return [$validator, $context];
    }

    #[DataProvider('provideValidTimezoneEnumValues')]
    public function testValidTimezoneEnumValueRaisesNoViolation(int $enumValue): void
    {
        [$validator, $context] = $this->createValidatorWithContext();
        $context->expects($this->never())
            ->method('buildViolation');

        $validator->validate($enumValue, new IsValidTimezone());
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
        [$validator, $context] = $this->createValidatorWithContext();
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

        $validator->validate($value, $constraint);
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
        [$validator, $context] = $this->createValidatorWithContext();
        $context->expects($this->never())
            ->method('buildViolation');

        $validator->validate(TimezoneEnum::BERLIN, new NotBlank());
    }

    public function testWrongConstraintTypeThrowsOnlyWhenValueIsAlsoInvalid(): void
    {
        [$validator] = $this->createValidatorWithContext();

        $this->expectException(\InvalidArgumentException::class);

        $validator->validate(999999, new NotBlank());
    }
}
