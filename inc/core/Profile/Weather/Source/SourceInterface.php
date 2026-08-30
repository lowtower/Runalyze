<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Source;

interface SourceInterface
{
    /**
     * @return int
     */
    public function getInternalProfileEnum();

    /**
     * @return bool
     */
    public function requiresAttribution();

    /**
     * @param bool $withLink
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAttribution(\Symfony\Contracts\Translation\TranslatorInterface $translator, $withLink = true);

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getAttributionLabel(\Symfony\Contracts\Translation\TranslatorInterface $translator);

    /**
     * @return string|null
     */
    public function getAttributionUrl();

    /**
     * @return bool
     */
    public function hasAttributionUrl();
}
