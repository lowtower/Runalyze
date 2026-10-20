<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ContainsDisposableMailAddress extends Constraint
{
    public string $message = 'Disposable email addresses are not permitted: "{{ value }}"';
}
