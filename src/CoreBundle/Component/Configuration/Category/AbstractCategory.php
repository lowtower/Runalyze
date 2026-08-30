<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

use Runalyze\Bundle\CoreBundle\Component\VariablesContainerTrait;

abstract class AbstractCategory
{
    use VariablesContainerTrait;

    public function __construct(array $variables = [])
    {
        $this->Variables = array_merge(
            $this->getDefaultVariables(),
            $variables
        );
    }

    /**
     * @return array ['key' => 'value']
     */
    abstract public function getDefaultVariables();

    abstract protected function getLegacyCategoryName(): string;

    public function getLegacyCategory()
    {
        $className = $this->getLegacyCategoryName();

        $category = new $className();
        $category->setValues($this->Variables);

        return $category;
    }
}
