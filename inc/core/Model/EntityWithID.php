<?php

declare(strict_types=1);

/**
 * This file contains class::EntityWithID
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

/**
 * Abstract object with ID
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
abstract class EntityWithID extends Entity
{
    /**
     * ID
     */
    protected ?int $ID = null;

    /**
     * Construct
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        if (isset($data['id'])) {
            $this->ID = (int) $data['id'];
        }
    }

    /**
     * Has ID?
     */
    public function hasID(): bool
    {
        return $this->ID !== null;
    }

    public function id(): ?int
    {
        return $this->ID;
    }

    /**
     * Use this method only if you know what you're doing!
     */
    public function setID(int $id)
    {
        $this->ID = $id;
    }
}
