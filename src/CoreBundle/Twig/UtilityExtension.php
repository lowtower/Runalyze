<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

class UtilityExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return 'runalyze.utility_extension';
    }

    /**
     * @return \Twig\TwigFilter[]
     *
     * @codeCoverageIgnore
     */
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('duration', [$this, 'duration']),
            new \Twig\TwigFilter('filesize', [$this, 'filesizeAsString']),
            new \Twig\TwigFilter('md5', [$this, 'md5']),
        ];
    }

    /**
     * @param float $seconds
     * @param int $decimals
     * @param string $decimalPoint
     * @return string
     */
    public function duration($seconds, $decimals = 0, $decimalPoint = ',')
    {
        $fraction = (round($seconds) !== round($seconds, $decimals) && $decimals > 0) ? $decimalPoint . 'u' : '';

        if ($seconds >= 86400) {
            return $this->formatDuration($seconds, 'z\d H:i:s');
        }

        if ($seconds >= 3600) {
            return $this->formatDuration($seconds, 'G:i:s');
        }

        if ($seconds < 60) {
            return '0:' . $this->formatDuration($seconds, 's' . $fraction, $decimals);
        }

        return ltrim($this->formatDuration($seconds, 'i:s' . $fraction, $decimals), '0');
    }

    /**
     * @param float $seconds
     * @param int $decimals
     * @return string
     */
    private function formatDuration($seconds, string $format, $decimals = 0)
    {
        if (str_ends_with($format, 'u')) {
            $time = \DateTime::createFromFormat('!U', (string) (int) floor($seconds), new \DateTimeZone('UTC'));
            $fraction = str_pad((string) round(fmod($seconds, 1) * 10 ** $decimals), $decimals, '0', STR_PAD_LEFT);

            return $time->format(substr($format, 0, -1)) . $fraction;
        }

        $time = \DateTime::createFromFormat('!U', (string) (int) round($seconds), new \DateTimeZone('UTC'));

        return $time->format($format);
    }

    /**
     * @param int $bytes
     */
    public function filesizeAsString($bytes): string
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $FS = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        return number_format($bytes / 1024 ** (($I = floor(log($bytes, 1024)))), ($I >= 1) ? 2 : 0, '.', '') . ' ' . $FS[(string) $I];
    }

    public function md5(string $value): string
    {
        return md5($value);
    }
}
