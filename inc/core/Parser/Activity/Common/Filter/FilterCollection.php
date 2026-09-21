<?php

namespace Runalyze\Parser\Activity\Common\Filter;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;

class FilterCollection
{
    /**
     * @var FilterInterface[]
     */
    protected $Filter = [];

    public function __construct(
        protected ?LoggerInterface $Logger = null
    ) {
    }

    public function add(FilterInterface $filter): void
    {
        if ($this->Logger instanceof LoggerInterface && $filter instanceof LoggerAwareInterface) {
            $filter->setLogger($this->Logger);
        }

        $this->Filter[] = $filter;
    }

    public function filter(ActivityDataContainer $container): void
    {
        foreach ($this->Filter as $filter) {
            $filter->filter($container);
        }
    }
}
