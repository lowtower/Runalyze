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
        return array(
            new \Twig_SimpleFilter('info', array($this, 'info'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('error', array($this, 'error'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('warning', array($this, 'warning'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('okay', array($this, 'okay'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('nbsp', array($this, 'nbsp'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('nonbsp', array($this, 'nonbsp'), array(
                'is_safe' => array('html')
            )),
        );
    }

    /**
     * @param string $string
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function info($string): string
    {
        return '<p class="info">'.$string.'</p>';
    }

    /**
     * @param string $string
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function error($string): string
    {
        return '<p class="error">'.$string.'</p>';
    }

    /**
     * @param string $string
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function warning($string): string
    {
        return '<p class="warning">'.$string.'</p>';
    }

    /**
     * @param string $string
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function okay($string): string
    {
        return '<p class="okay">'.$string.'</p>';
    }

    /**
     * @param string $string
     * @return string
     */
    public function nbsp($string): string
    {
        return str_replace(' ', '&nbsp;', $string);
    }

    /**
     * @param string $string
     * @return string
     */
    public function nonbsp($string): string
    {
        return str_replace('&nbsp;', ' ', $string);
    }
}
