<?php

/**
 * This file contains class::SelectRow
 * @package Runalyze\Parameter
 */

namespace Runalyze\Parameter;

/**
 * Select row from database
 * @author Hannes Christiansen
 * @package Runalyze\Parameter
 */
class SelectRow extends Select
{
    protected bool $UseFallback = false;

    /**
     * Construct
     * @param string $default
     * @param array $options [optional]
     */
    public function __construct($default, array $options = [])
    {
        $options = array_merge(
            [
                'table' => '',
                'column' => '',
            ],
            $options
        );

        parent::__construct($default, $options);
    }

    /**
     * @return string
     */
    public function table()
    {
        return $this->Options['table'];
    }

    /**
     * @return string
     */
    public function column()
    {
        return $this->Options['column'];
    }

    /**
     * Value allowed?
     */
    protected function valueIsAllowed(string $value): bool
    {
        return true;
    }
}
