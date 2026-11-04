<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
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

    /**
     * Wraps captured legacy content in the appropriate response: bare content
     * for XHR window loads, the twig page shell (legacy_window.html.twig) for
     * direct full-page requests.
     */
    protected function wrapLegacyContent(string $legacyContent, ?Request $request): Response
    {
        if (! $request instanceof \Symfony\Component\HttpFoundation\Request || $request->isXmlHttpRequest()) {
            return new Response($legacyContent);
        }

        /** @phpstan-ignore-next-line this trait is only used in AbstractController subclasses */
        return $this->render('legacy_window.html.twig', [
            'legacyContent' => $legacyContent,
        ]);
    }
}
