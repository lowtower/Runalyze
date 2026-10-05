<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity\Common;

interface NamedEntityInterface
{
    public function getName(): string;
}
