<?php

namespace Runalyze\Bundle\CoreBundle\Validator\Constraints;

use Runalyze\Parameter\Application\Timezone;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsValidTimezoneValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (! Timezone::isValidValue($value)) {
            if (! $constraint instanceof IsValidTimezone) {
                throw new \InvalidArgumentException('Expected constraint of type IsValidTimezone.');
            }

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }
    }
}
