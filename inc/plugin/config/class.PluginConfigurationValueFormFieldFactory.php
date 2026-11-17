<?php

declare(strict_types=1);

final class PluginConfigurationValueFormFieldFactory
{
    public function create(PluginConfigurationValue $value): ?FormularField
    {
        if ($value instanceof PluginConfigurationValueHiddenArray) {
            return null;
        }

        $label = $value->tooltip() !== ''
            ? Ajax::tooltip($value->label(), $value->tooltip(), true)
            : $value->label();

        if ($value instanceof PluginConfigurationValueBool) {
            return new FormularCheckbox($value->key(), $label, $value->valueAsString());
        }

        if ($value instanceof PluginConfigurationValueSelect) {
            $field = new FormularSelectBox($value->key(), $label, $value->valueAsString());
            $field->setOptions($value->options());

            return $field;
        }

        if ($value instanceof PluginConfigurationValueDistance) {
            $field = new FormularInput($value->key(), $label, $value->valueAsString(true));
            $field->setSize(FormularInput::$SIZE_SMALL);
            $field->setUnit($value->usesImperialUnits() ? FormularUnit::$MILES : FormularUnit::$KM);

            return $field;
        }

        if ($value instanceof PluginConfigurationValueDistances) {
            $field = new FormularInput($value->key(), $label, $value->valueAsString());
            $field->setSize(FormularInput::$SIZE_FULL_INLINE);
            $field->addAttribute('maxlength', (string) PluginConfigurationValue::MAXLENGTH);
            $field->setUnit($value->usesImperialUnits() ? FormularUnit::$MILES : FormularUnit::$KM);

            return $field;
        }

        if ($value instanceof PluginConfigurationValueArray) {
            $field = new FormularInput($value->key(), $label, $value->valueAsString());
            $field->setSize(FormularInput::$SIZE_FULL_INLINE);
            $field->addAttribute('maxlength', (string) PluginConfigurationValue::MAXLENGTH);

            return $field;
        }

        $field = new FormularInput($value->key(), $label, $value->valueAsString());
        $field->setSize(
            $value instanceof PluginConfigurationValueFloat || $value instanceof PluginConfigurationValueInt
                ? FormularInput::$SIZE_SMALL
                : FormularInput::$SIZE_MIDDLE
        );

        return $field;
    }
}
