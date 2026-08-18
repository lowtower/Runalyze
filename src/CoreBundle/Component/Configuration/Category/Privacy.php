<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class Privacy extends AbstractCategory
{
    /**
     * @return array
     */
    public function getDefaultVariables()
    {
        return [
            'TRAINING_LIST_PUBLIC' => 'false',
            'TRAINING_LIST_ALL' => 'false',
            'TRAINING_LIST_STATISTICS' => 'false',
            'TRAINING_MAP_PUBLIC_MODE' => 'always',
        ];
    }

    /**
     * @return string
     */
    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\Privacy::class;
    }

    /**
     * @return bool
     */
    public function isListPublic(): bool
    {
        return 'true' == $this->Variables['TRAINING_LIST_PUBLIC'];
    }

    /**
     * @return bool
     */
    public function isListShowingAllActivities(): bool
    {
        return 'true' == $this->Variables['TRAINING_LIST_ALL'];
    }

    /**
     * @return bool
     */
    public function isListWithStatistics(): bool
    {
        return 'true' == $this->Variables['TRAINING_LIST_STATISTICS'];
    }
}
