<?php

/**
 * This file contains class::DatabaseOrder
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\NamedEntityInterface;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Parameter\Application
 */
class DatabaseOrder extends \Runalyze\Parameter\Select
{
    /**
     * ID: ascending
     */
    public const string ASC = 'id-asc';

    /**
     * ID: descending
     */
    public const string DESC = 'id-desc';

    /**
     * Alphabetical
     */
    public const string ALPHA = 'alpha';

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct(self::ASC, [
            'options' => [
                self::ASC => __('id (oldest first)'),
                self::DESC => __('id (latest first)'),
                self::ALPHA => __('alphabetical'),
            ],
        ]);
    }

    /**
     * As mysql query-string
     */
    public function asQuery(): string
    {
        return match ($this->value()) {
            self::ALPHA => 'ORDER BY `name` ASC',
            self::DESC => 'ORDER BY `id` DESC',
            default => 'ORDER BY `id` ASC',
        };
    }

    /**
     * Sort data
     */
    public function sort(array &$data): void
    {
        $key = ($this->value() == self::ALPHA) ? 'name' : 'id';
        $desc = ($this->value() == self::DESC) ? -1 : 1;

        uasort($data, function (array $a, array $b) use ($key, $desc): int {
            if ($a[$key] == $b[$key]) {
                return 0;
            }

            if ($a[$key] < $b[$key]) {
                return -1 * $desc;
            }

            return $desc;
        });
    }

    public function sortCollection(Collection $collection): Collection
    {
        $key = ($this->value() == self::ALPHA) ? 'name' : 'id';
        $desc = ($this->value() == self::DESC) ? -1 : 1;

        /** @var \ArrayIterator $iterator */
        $iterator = $collection->getIterator();
        $iterator->uasort(function ($a, $b) use ($key, $desc): int {
            if ($key === 'name' && $a instanceof NamedEntityInterface && $b instanceof NamedEntityInterface) {
                if ($a->getName() === $b->getName()) {
                    return 0;
                }

                return ($a->getName() < $b->getName()) ? -1 * $desc : $desc;
            }

            if ($key === 'id' && $a instanceof IdentifiableEntityInterface && $b instanceof IdentifiableEntityInterface) {
                if ($a->getId() == $b->getId()) {
                    return 0;
                }

                return ($a->getId() < $b->getId()) ? -1 * $desc : $desc;
            }

            return 0;
        });

        return new ArrayCollection(iterator_to_array($iterator));
    }
}
