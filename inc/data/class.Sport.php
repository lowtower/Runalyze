<?php

use Runalyze\Profile\Sport\SportProfile;

/**
 * @deprecated
 */
class Sport
{
    /**
     * Array with all information from database
     */
    private array $data;

    public function __construct(
        /**
         * ID for this sport in database
         */
        private int|bool $id = false
    ) {
        $this->data = SportFactory::DataFor($this->id);
    }

    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get name
     */
    public function name(): string
    {
        return $this->data['name'];
    }

    /*
     * Has a training of this sport a distance?
     * @return bool
     */
    public function usesDistance(): bool
    {
        return $this->data['distances'] == 1;
    }

    /**
     * Checks if this sport is set as "Running"
     */
    public function isRunning(): bool
    {
        return $this->data['internal_sport_id'] == SportProfile::RUNNING;
    }
}
