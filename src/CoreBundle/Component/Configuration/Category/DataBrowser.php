<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class DataBrowser extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'DB_DISPLAY_MODE' => 'week',
            'DB_SHOW_DATASET_LABELS' => 'true',
            'DB_SHOW_CREATELINK_FOR_DAYS' => 'false',
            'DB_SHOW_ACTIVE_DAYS_ONLY' => 'false',
            'DB_REVERSE_MODE' => 'false',
        ];
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\DataBrowser::class;
    }
}
