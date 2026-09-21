<?php

/**
 * This file contains class::FieldFactory
 * @package Runalyze\Configuration
 */

namespace Runalyze\Configuration;

use Runalyze\Parameter\Boolean;
use Runalyze\Parameter\Select;
use Runalyze\Parameter\SelectRow;

/**
 * Factory for configuration fields
 * @author Hannes Christiansen
 * @package Runalyze\Configuration
 */
class FieldFactory
{
    /**
     * Add field
     * @param array<string, mixed> $options
     */
    public function FieldFor(Handle $Handle, array $options = []): \FormularField
    {
        $options = array_merge([
            'label' => $Handle->key(),
            'tooltip' => '',
            'unit' => '',
            'size' => '',
            'css' => '',
            'layout' => '',
        ], $options);

        $label = ! empty($options['tooltip']) ? \Ajax::tooltip($options['label'], $options['tooltip']) : $options['label'];

        $Field = $this->createFieldFor($Handle, $label);
        $this->setAttributesToField($Field, $options);

        return $Field;
    }

    /**
     * Create a field
     */
    private function createFieldFor(Handle $Handle, string $label): \FormularField
    {
        $Parameter = $Handle->object();
        $Class = 'FormularInput';

        if ($Parameter instanceof SelectRow) {
            $Field = new \FormularSelectDb($Handle->key(), $label, $Handle->value());
            $Field->loadOptionsFrom($Parameter->table(), $Parameter->column());

            return $Field;
        }

        if ($Parameter instanceof Select) {
            $Field = new \FormularSelectBox($Handle->key(), $label, $Handle->value());
            $Field->setOptions($Parameter->options());

            return $Field;
        }

        if ($Parameter instanceof Boolean) {
            $Field = new \FormularCheckbox($Handle->key(), $label, $Handle->value());
            $Field->addHiddenSentValue();

            return $Field;
        }

        return new $Class($Handle->key(), $label, $Handle->object()->valueAsString());
    }

    /**
     * @param array<string, mixed> $options
     */
    private function setAttributesToField(\FormularField $Field, array &$options): void
    {
        if (! empty($options['unit']) && method_exists($Field, 'setUnit')) {
            $Field->setUnit($options['unit']);
        }

        if (! empty($options['size']) && method_exists($Field, 'setSize')) {
            $Field->setSize($options['size']);
        }

        if (! empty($options['css'])) {
            $Field->addLayoutClass($options['css']);
        }

        if (! empty($options['layout'])) {
            $Field->setLayout($options['layout']);
        }
    }
}
