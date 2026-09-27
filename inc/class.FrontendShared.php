<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @deprecated since v3.1
 */
class FrontendShared extends Frontend
{
    public static bool $IS_SHOWN = false;

    public function __construct(
        bool $hideHeaderAndFooter = false,
        ?TokenStorageInterface $symfonyToken = null,
        ?ParameterBagInterface $parameterBag = null
    ) {
        self::$IS_SHOWN = true;

        parent::__construct($hideHeaderAndFooter, $symfonyToken, $parameterBag);
    }

    public function displayHeader(): void
    {
        self::$IS_SHOWN = true;
    }

    public function displayFooter(): void
    {
    }

    protected function initSessionAccountHandler(): void
    {
    }
}
