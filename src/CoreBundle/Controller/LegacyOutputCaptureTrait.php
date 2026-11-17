<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wraps rendered legacy content in the appropriate response.
 */
trait LegacyOutputCaptureTrait
{
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
