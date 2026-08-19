<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

class HtmlExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return 'runalyze.html_extension';
    }

    /**
     * @return \Twig\TwigFilter[]
     *
     * @codeCoverageIgnore
     */
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('info', [$this, 'info'], [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('error', [$this, 'error'], [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('warning', [$this, 'warning'], [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('okay', [$this, 'okay'], [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('nbsp', [$this, 'nbsp'], [
                'is_safe' => ['html'],
            ]),
            new \Twig\TwigFilter('nonbsp', [$this, 'nonbsp'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * @param string $string
     *
     * @codeCoverageIgnore
     */
    public function info($string): string
    {
        return '<p class="info">' . $string . '</p>';
    }

    /**
     * @param string $string
     *
     * @codeCoverageIgnore
     */
    public function error($string): string
    {
        return '<p class="error">' . $string . '</p>';
    }

    /**
     * @param string $string
     *
     * @codeCoverageIgnore
     */
    public function warning($string): string
    {
        return '<p class="warning">' . $string . '</p>';
    }

    /**
     * @param string $string
     *
     * @codeCoverageIgnore
     */
    public function okay($string): string
    {
        return '<p class="okay">' . $string . '</p>';
    }

    /**
     * @param string $string
     */
    public function nbsp($string): string
    {
        return str_replace(' ', '&nbsp;', $string);
    }

    /**
     * @param string $string
     */
    public function nonbsp($string): string
    {
        return str_replace('&nbsp;', ' ', $string);
    }
}
