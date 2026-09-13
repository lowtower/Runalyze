<?php

/**
 * This file contains class::Context
 * @package Runalyze
 */

namespace Runalyze\Dataset;

use Runalyze\Model;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\View\Activity\Dataview;
use Runalyze\View\Activity\Linker;

/**
 * Context for dataset view
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset
 */
class Context
{
    /**
     * Factory
     */
    protected Model\Factory $Factory;

    /**
     * Activity
     */
    protected Model\Activity\Entity $Activity;

    protected array $ActivityData = [];

    /**
     * Sport
     */
    protected ?Model\Sport\Entity $Sport = null;

    /**
     * Type
     */
    protected ?Model\Type\Entity $Type = null;

    /**
     * Dataview
     */
    protected Dataview $Dataview;

    /**
     * Activity linker
     */
    protected Linker $Linker;

    protected bool $IsRunning = false;

    /**
     * Construct dataset context for activity
     * @param array|Model\Activity\Entity $dataOrObject
     * @throws \InvalidArgumentException
     */
    public function __construct($dataOrObject, int $accountID)
    {
        $this->Factory = new Model\Factory($accountID);

        if (is_array($dataOrObject)) {
            $this->setActivityData($dataOrObject);
        } elseif ($dataOrObject instanceof Model\Activity\Entity) {
            $this->setActivity($dataOrObject);
        } else {
            throw new \InvalidArgumentException('Provided data must be an array or activity object.');
        }
    }

    public function setActivity(Model\Activity\Entity $object): void
    {
        $this->Activity = $object;
        $this->Dataview = new Dataview($object);
        $this->Linker = new Linker($object);
        $this->Sport = $this->Activity->sportid() > 0 ? $this->Factory->sport($this->Activity->sportid()) : null;
        $this->Type = $this->Activity->typeid() > 0 ? $this->Factory->type($this->Activity->typeid()) : null;
        $this->ActivityData = $this->Activity->completeData();
        $this->IsRunning = $this->hasSport() && $this->Sport->getInternalProfileEnum() === SportProfile::RUNNING;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setActivityData(array $data): void
    {
        if ($data === []) {
            throw new \InvalidArgumentException('Provided data must not be empty.');
        }

        $this->setActivity(new Model\Activity\Entity($data));
        $this->ActivityData = $data;
    }

    public function factory(): Model\Factory
    {
        return $this->Factory;
    }

    public function activity(): Model\Activity\Entity
    {
        return $this->Activity;
    }

    public function dataview(): Dataview
    {
        return $this->Dataview;
    }

    public function linker(): Linker
    {
        return $this->Linker;
    }

    public function hasSport(): bool
    {
        return $this->Sport instanceof \Runalyze\Model\Sport\Entity;
    }

    public function sport(): Model\Sport\Entity
    {
        return $this->Sport;
    }

    public function isRunning(): bool
    {
        return $this->IsRunning;
    }

    public function hasType(): bool
    {
        return $this->Type instanceof \Runalyze\Model\Type\Entity;
    }

    public function type(): Model\Type\Entity
    {
        return $this->Type;
    }

    public function hasData(string $key): bool
    {
        return isset($this->ActivityData[$key]);
    }

    /**
     * Get additional data that is not in the activity object
     * @param bool $returnNullForUnknownKeys set to true to not throw an exception for unknown keys
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function data(string $key, bool $returnNullForUnknownKeys = false)
    {
        if (! isset($this->ActivityData[$key])) {
            if ($returnNullForUnknownKeys) {
                return null;
            }

            throw new \InvalidArgumentException('Provided key "' . $key . '" does not exist in activity data.');
        }

        return $this->ActivityData[$key];
    }

    /**
     * @param mixed $value
     */
    public function setData(string $key, $value): void
    {
        $this->ActivityData[$key] = $value;
    }
}
