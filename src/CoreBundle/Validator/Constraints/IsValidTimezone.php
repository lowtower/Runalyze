<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsValidTimezone extends Constraint
{
    #    public string $message = 'Invalid timezone';
    public string $message = 'Invalid timezone: "{{ value }}"';
}
