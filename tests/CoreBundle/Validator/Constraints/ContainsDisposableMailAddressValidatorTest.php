<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\ContainsDisposableMailAddress;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\ContainsDisposableMailAddressValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ContainsDisposableMailAddressValidatorTest extends TestCase
{
    /**
     * For tests that only need a context object to exist (no expectations
     * are ever put on it).
     *
     * @return array{0: ContainsDisposableMailAddressValidator, 1: ExecutionContextInterface&Stub}
     */
    private function createValidatorWithStubContext(): array
    {
        $validator = new ContainsDisposableMailAddressValidator();
        $context = $this->createStub(ExecutionContextInterface::class);
        $validator->initialize($context);

        return [$validator, $context];
    }

    /**
     * For tests that put expects() on the context (e.g. buildViolation).
     *
     * @return array{0: ContainsDisposableMailAddressValidator, 1: ExecutionContextInterface&MockObject}
     */
    private function createValidatorWithMockContext(): array
    {
        $validator = new ContainsDisposableMailAddressValidator();
        $context = $this->createMock(ExecutionContextInterface::class);
        $validator->initialize($context);

        return [$validator, $context];
    }

    private function isActuallyDisposable(string $email): bool
    {
        return new \EmailValidator\Validator()->isDisposable($email);
    }

    public function testDisposableAddressRaisesViolation(): void
    {
        $email = 'someone@mailinator.com';

        if (! $this->isActuallyDisposable($email)) {
            $this->markTestSkipped(
                'The configured EmailValidator\Validator does not classify "' . $email . '" as disposable ' .
                'in this environment; adjust the test email to a domain that this specific dependency flags as disposable.'
            );
        }

        [$validator, $context] = $this->createValidatorWithMockContext();
        $constraint = new ContainsDisposableMailAddress();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $email)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('addViolation');

        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $validator->validate($email, $constraint);
    }

    public function testNonDisposableAddressRaisesNoViolation(): void
    {
        $email = 'valid.user@runalyze.com';

        if ($this->isActuallyDisposable($email)) {
            $this->markTestSkipped(
                'The configured EmailValidator\Validator unexpectedly classifies "' . $email . '" as disposable ' .
                'in this environment; adjust the test email to one that this dependency does NOT flag as disposable.'
            );
        }

        [$validator, $context] = $this->createValidatorWithMockContext();
        $context->expects($this->never())
            ->method('buildViolation');

        $validator->validate($email, new ContainsDisposableMailAddress());
    }

    public function testWrongConstraintTypeIsSilentlyIgnoredForNonDisposableAddress(): void
    {
        $email = 'valid.user@runalyze.com';

        if ($this->isActuallyDisposable($email)) {
            $this->markTestSkipped(
                'The configured EmailValidator\Validator unexpectedly classifies "' . $email . '" as disposable ' .
                'in this environment; adjust the test email to one that this dependency does NOT flag as disposable.'
            );
        }

        [$validator, $context] = $this->createValidatorWithMockContext();
        $context->expects($this->never())
            ->method('buildViolation');

        $validator->validate($email, new NotBlank());
    }

    public function testWrongConstraintTypeThrowsOnlyForDisposableAddress(): void
    {
        $email = 'someone@mailinator.com';

        if (! $this->isActuallyDisposable($email)) {
            $this->markTestSkipped(
                'The configured EmailValidator\Validator does not classify "' . $email . '" as disposable ' .
                'in this environment; adjust the test email to a domain that this specific dependency flags as disposable.'
            );
        }

        [$validator] = $this->createValidatorWithStubContext();

        $this->expectException(\InvalidArgumentException::class);

        $validator->validate($email, new NotBlank());
    }
}
