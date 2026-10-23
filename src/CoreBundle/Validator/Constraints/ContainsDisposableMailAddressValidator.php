<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Validator\Constraints;

use Runalyze\Bundle\CoreBundle\Services\DisposableDomainProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ContainsDisposableMailAddressValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DisposableDomainProvider $provider
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ContainsDisposableMailAddress) {
            throw new UnexpectedTypeException($constraint, ContainsDisposableMailAddress::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $email = strtolower(trim((string) $value));

        if (! str_contains($email, '@')) {
            return;
        }

        $domain = substr($email, strpos($email, '@') + 1);

        $domains = $this->provider->getDomains();

        if (isset($domains[$domain])) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }
    }
}
