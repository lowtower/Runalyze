<?php

/**
 * This file contains class::Fieldset
 * @package Runalyze\Configuration
 */

namespace Runalyze\Configuration;

/**
 * Configuration fieldset
 * @author Hannes Christiansen
 * @package Runalyze\Configuration
 */
class Fieldset extends \FormularFieldset
{
    protected FieldFactory $Factory;

    /**
     * Construct new form
     */
    public function __construct(string $title)
    {
        parent::__construct($title);

        $this->Factory = new FieldFactory();
    }

    public function addHandle(Handle $Handle, array $options = []): void
    {
        $this->addField($this->Factory->FieldFor($Handle, $options));
    }
}
