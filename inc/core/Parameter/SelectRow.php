<?php

declare(strict_types=1);

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
    #[\Override]
    protected bool $UseFallback = false;

    public function __construct(string $default, array $options = [])
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

    public function table(): string
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

    protected function valueIsAllowed(string $value): bool
    {
        return true;
    }
}
