<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

class HtmlExtension extends \Twig_Extension
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
     * @return \Twig_SimpleFilter[]
     *
     * @codeCoverageIgnore
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('info', [$this, 'info'], [
                'is_safe' => ['html'],
            ]),
            new \Twig_SimpleFilter('error', [$this, 'error'], [
                'is_safe' => ['html'],
            ]),
            new \Twig_SimpleFilter('warning', [$this, 'warning'], [
                'is_safe' => ['html'],
            ]),
            new \Twig_SimpleFilter('okay', [$this, 'okay'], [
                'is_safe' => ['html'],
            ]),
            new \Twig_SimpleFilter('nbsp', [$this, 'nbsp'], [
                'is_safe' => ['html'],
            ]),
            new \Twig_SimpleFilter('nonbsp', [$this, 'nonbsp'], [
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
