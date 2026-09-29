<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Configuration;

use Runalyze\Bundle\CoreBundle\Component\VariablesContainerTrait;

class ConfigurationList
{
    use VariablesContainerTrait;

    public function __construct(array $variables)
    {
        $this->Variables = $variables;
    }

    public function mergeWith(array $variables): void
    {
        $this->Variables = array_merge($this->Variables, $variables);
    }
}
