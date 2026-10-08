<?php

namespace Runalyze\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ContainsDisposableMailAddressValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (new \EmailValidator\Validator()->isDisposable($value)) {
            if (! $constraint instanceof ContainsDisposableMailAddress) {
                throw new \InvalidArgumentException('Expected constraint of type ContainsDisposableMailAddress.');
            }

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }
    }
}
