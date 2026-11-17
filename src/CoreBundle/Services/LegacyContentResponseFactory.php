<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class LegacyContentResponseFactory
{
    public function __construct(
        private Environment $twig
    ) {}

    public function create(string $content, ?Request $request): Response
    {
        if (!$request instanceof Request || $request->isXmlHttpRequest()) {
            return new Response($content);
        }

        return new Response($this->twig->render('legacy_window.html.twig', [
            'legacyContent' => $content,
        ]));
    }
}
