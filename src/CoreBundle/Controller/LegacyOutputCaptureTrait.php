<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Runs legacy code that echoes HTML directly into an output buffer and returns the captured
 * output as a Response, instead of letting it reach the client directly.
 */
trait LegacyOutputCaptureTrait
{
    /**
     * @throws Throwable if the given callback throws
     */
    protected function captureLegacyOutput(callable $callback): Response
    {
        ob_start();

        try {
            $callback();
            $content = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return new Response($content);
    }
}
