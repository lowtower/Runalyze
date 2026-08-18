<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class Trimp extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'ATL_DAYS' => '7',
            'CTL_DAYS' => '42',
            'TRIMP_MODEL_IN_PERCENT' => 'true',
            'TSB_IN_PERCENT' => 'false',
        ];
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\Trimp::class;
    }
}
