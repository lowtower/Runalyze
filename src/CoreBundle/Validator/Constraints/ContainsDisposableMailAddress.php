<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ContainsDisposableMailAddress extends Constraint
{
    public string $message = 'Disposable email addresses are not permitted: "{{ value }}"';
}
