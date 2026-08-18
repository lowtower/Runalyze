<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity\Common;

interface IdentifiableEntityInterface
{
    /**
     * @return int
     */
    public function getId();
}
