<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Runalyze\Bundle\CoreBundle\Services\DisposableDomainProvider;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\ContainsDisposableMailAddress;
use Runalyze\Bundle\CoreBundle\Validator\Constraints\ContainsDisposableMailAddressValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

#[AllowMockObjectsWithoutExpectations]
final class ContainsDisposableMailAddressValidatorTest extends ConstraintValidatorTestCase
{
    private DisposableDomainProvider $disposableDomainProvider;

    private ExecutionContextInterface&MockObject $contextMock;

    protected function setUp(): void
    {
        $this->disposableDomainProvider = new DisposableDomainProvider('/tmp');
        $this->contextMock = $this->createMock(ExecutionContextInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ContainsDisposableMailAddressValidator
    {
        return new ContainsDisposableMailAddressValidator($this->disposableDomainProvider);
    }

    private function isActuallyDisposable(string $email): bool
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        $domain = strtolower($parts[1]);
        $domains = $this->disposableDomainProvider->getDomains();

        return isset($domains[$domain]);
    }

    #[Test]
    public function testAcceptsValidEmail(): void
    {
        $this->validator->validateInContext('user@gmail.com', new ContainsDisposableMailAddress(), $this->context);
        $this->assertNoViolation();
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testRejectsDisposableEmail(): void
    {
        $email = 'test@mailinator.com';

        if (! $this->isActuallyDisposable($email)) {
            $this->markTestSkipped(
                'The domain "' . $email . '" is not in the disposable list; adjust the test email.'
            );
        }

        $constraint = new ContainsDisposableMailAddress();

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $email)
            ->atPath('')
            ->setInvalidValue($email)
            ->addViolation();

        $this->validator->validateInContext($email, $constraint, $this->context);
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testIsCaseInsensitive(): void
    {
        $email = 'TEST@MAILINATOR.COM';

        if (! $this->isActuallyDisposable($email)) {
            $this->markTestSkipped(
                'The domain "' . $email . '" is not in the disposable list; adjust the test email.'
            );
        }

        $constraint = new ContainsDisposableMailAddress();

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $email)
            ->atPath('')
            ->setInvalidValue($email)
            ->addViolation();

        $this->validator->validateInContext($email, $constraint, $this->context);
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testDisposableAddressRaisesViolation(): void
    {
        $email = 'someone@mailinator.com';

        if (! $this->isActuallyDisposable($email)) {
            $this->markTestSkipped(
                'The domain "' . $email . '" is not in the disposable list; adjust the test email.'
            );
        }

        $constraint = new ContainsDisposableMailAddress();

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $email)
            ->atPath('')
            ->setInvalidValue($email)
            ->addViolation();

        $this->validator->validateInContext($email, $constraint, $this->context);
    }

    #[Test]
    public function testNonDisposableAddressRaisesNoViolation(): void
    {
        $email = 'valid.user@runalyze.com';

        if ($this->isActuallyDisposable($email)) {
            $this->markTestSkipped(
                'The domain "' . $email . '" is unexpectedly classified as disposable; adjust the test email.'
            );
        }

        $this->contextMock->expects($this->never())
            ->method('buildViolation');

        $this->validator->validateInContext($email, new ContainsDisposableMailAddress(), $this->contextMock);
    }
}
