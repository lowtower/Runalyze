<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class LegacyPluginOutputRenderer
{
    /**
     * @param callable(): void $callback
     *
     * @throws Throwable if the legacy callback throws while rendering
     */
    public function render(callable $callback): Response
    {
        ob_start();

        try {
            $callback();
            $content = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return new Response($content === false ? '' : $content);
    }
}
