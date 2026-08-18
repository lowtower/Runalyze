<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;

abstract class AbstractTokenStorageAwareType extends AbstractType
{
    use TokenStorageAwareTypeTrait;
}
