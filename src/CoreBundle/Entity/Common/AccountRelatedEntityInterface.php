<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity\Common;

use Runalyze\Bundle\CoreBundle\Entity\Account;

interface AccountRelatedEntityInterface
{
    public function getAccount(): ?Account;
}
