<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Twig;

class HtmlExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * @codeCoverageIgnore
     */
    public function getName(): string
    {
        return 'runalyze.html_extension';
    }

    /**
     * @return \Twig\TwigFilter[]
     *
     * @codeCoverageIgnore
     */
    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('info', $this->info(...), [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('error', $this->error(...), [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('warning', $this->warning(...), [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('okay', $this->okay(...), [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('nbsp', $this->nbsp(...), [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('nonbsp', $this->nonbsp(...), [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function info(string $string): string
    {
        return '<p class="info">' . $string . '</p>';
    }

    /**
     * @codeCoverageIgnore
     */
    public function error(string $string): string
    {
        return '<p class="error">' . $string . '</p>';
    }

    /**
     * @codeCoverageIgnore
     */
    public function warning(string $string): string
    {
        return '<p class="warning">' . $string . '</p>';
    }

    /**
     * @codeCoverageIgnore
     */
    public function okay(string $string): string
    {
        return '<p class="okay">' . $string . '</p>';
    }

    public function nbsp(string $string): string
    {
        return str_replace(' ', '&nbsp;', $string);
    }

    public function nonbsp(string $string): string
    {
        return str_replace('&nbsp;', ' ', $string);
    }
}
