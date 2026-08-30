<?php

namespace Runalyze\Profile\Weather\Source;

abstract class AbstractSource implements SourceInterface
{
    public function requiresAttribution()
    {
        return true;
    }

    public function getAttribution(\Symfony\Contracts\Translation\TranslatorInterface $translator, $withLink = true)
    {
        if ($withLink && $this->hasAttributionUrl()) {
            return sprintf('<a href="%s" target="_blank">%s</a>', $this->getAttributionUrl(), $this->getAttributionLabel($translator));
        }

        return $this->getAttributionLabel($translator);
    }

    public function getAttributionUrl()
    {
        return null;
    }

    public function hasAttributionUrl()
    {
        return $this->getAttributionUrl() !== null;
    }
}
