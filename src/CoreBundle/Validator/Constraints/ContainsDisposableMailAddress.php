<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContainsDisposableMailAddress extends Constraint
{
    public $message = 'Disposable email addresses are not permitted.';
}
