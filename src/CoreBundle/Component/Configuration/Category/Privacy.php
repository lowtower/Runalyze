<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class Privacy extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'TRAINING_LIST_PUBLIC' => 'false',
            'TRAINING_LIST_ALL' => 'false',
            'TRAINING_LIST_STATISTICS' => 'false',
            'TRAINING_MAP_PUBLIC_MODE' => 'always',
        ];
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\Privacy::class;
    }

    public function isListPublic(): bool
    {
        return $this->Variables['TRAINING_LIST_PUBLIC'] == 'true';
    }

    public function isListShowingAllActivities(): bool
    {
        return $this->Variables['TRAINING_LIST_ALL'] == 'true';
    }

    public function isListWithStatistics(): bool
    {
        return $this->Variables['TRAINING_LIST_STATISTICS'] == 'true';
    }
}
