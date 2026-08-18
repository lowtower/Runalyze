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
        return 'true' == $this->Variables['TRAINING_LIST_PUBLIC'];
    }

    public function isListShowingAllActivities(): bool
    {
        return 'true' == $this->Variables['TRAINING_LIST_ALL'];
    }

    public function isListWithStatistics(): bool
    {
        return 'true' == $this->Variables['TRAINING_LIST_STATISTICS'];
    }
}
