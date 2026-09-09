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
    /**
     * @var null|Entity|Loopable
     */
    protected $Object;

    /**
     * Construct
     */
    public function __construct(?Entity $object)
    {
        parent::__construct($object);
    }

    /**
     * Current stroke
     * @return int
     */
    public function stroke()
    {
        return $this->current(Entity::STROKE);
    }

    /**
     * Current stroke
     * @return int
     */
    public function stroketype()
    {
        return $this->current(Entity::STROKETYPE);
    }

    /**
     * Current swolf
     * @return int
     */
    public function swolf()
    {
        return $this->current(Entity::SWOLF);
    }

    protected function createNewObject(array $data): Entity
    {
        return new Entity($data);
    }
}
