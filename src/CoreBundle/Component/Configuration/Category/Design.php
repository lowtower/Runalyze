<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class Design extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'DESIGN_BG_FILE' => 'runalyze.jpg',
        ];
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\Design::class;
    }
}
