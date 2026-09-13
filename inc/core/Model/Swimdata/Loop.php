<?php

declare(strict_types=1);

/**
 * This file contains class::Loop
 * @package Runalyze\Model\Swimdata
 */

namespace Runalyze\Model\Swimdata;

use Runalyze\Model\Loopable;

/**
 * Loop through swimdata object
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\Model\Swimdata
 */
class Loop extends \Runalyze\Model\Loop
{
    protected ?Loopable $Object = null;

    /**
     * Construct
     */
    public function __construct(?Entity $object)
    {
        parent::__construct($object);
    }

    /**
     * Current stroke
     */
    public function stroke(): int
    {
        return (int) $this->current(Entity::STROKE);
    }

    /**
     * Current stroke
     */
    public function stroketype(): int
    {
        return (int) $this->current(Entity::STROKETYPE);
    }

    /**
     * Current swolf
     */
    public function swolf(): int
    {
        return (int) $this->current(Entity::SWOLF);
    }

    protected function createNewObject(array $data): Entity
    {
        return new Entity($data);
    }
}
