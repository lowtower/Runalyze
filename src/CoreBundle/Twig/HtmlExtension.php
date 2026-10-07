<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Twig;

class HtmlExtension
{
    /**
     * @codeCoverageIgnore
     */
    #[\Twig\Attribute\AsTwigFilter(name: 'info', isSafe: ['html'])]
    public function info(string $string): string
    {
        return '<p class="info">' . $string . '</p>';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Twig\Attribute\AsTwigFilter(name: 'error', isSafe: ['html'])]
    public function error(string $string): string
    {
        return '<p class="error">' . $string . '</p>';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Twig\Attribute\AsTwigFilter(name: 'warning', isSafe: ['html'])]
    public function warning(string $string): string
    {
        return '<p class="warning">' . $string . '</p>';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Twig\Attribute\AsTwigFilter(name: 'okay', isSafe: ['html'])]
    public function okay(string $string): string
    {
        return '<p class="okay">' . $string . '</p>';
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'nbsp', isSafe: ['html'])]
    public function nbsp(string $string): string
    {
        return str_replace(' ', '&nbsp;', $string);
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'nonbsp', isSafe: ['html'])]
    public function nonbsp(string $string): string
    {
        return str_replace('&nbsp;', ' ', $string);
    }
}
