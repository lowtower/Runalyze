<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

class UtilityExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'duration')]
    public function duration(float $seconds, int $decimals = 0, string $decimalPoint = ','): string
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

    private function formatDuration(float $seconds, string $format, int $decimals = 0): string
    {
        if (str_ends_with($format, 'u')) {
            $time = \DateTime::createFromFormat('!U', (string) (int) floor($seconds), new \DateTimeZone('UTC'));
            $fraction = str_pad((string) round(fmod($seconds, 1) * 10 ** $decimals), $decimals, '0', STR_PAD_LEFT);

            return $time->format(substr($format, 0, -1)) . $fraction;
        }

        $time = \DateTime::createFromFormat('!U', (string) (int) round($seconds), new \DateTimeZone('UTC'));

        return $time->format($format);
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'filesize')]
    public function filesizeAsString(float $bytes): string
    {
        if ($bytes < 1.0) {
            return '0 B';
        }

        $FS = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $exponent = (int) floor(log($bytes, 1024));
        $exponent = min($exponent, count($FS) - 1);

        return number_format($bytes / 1024 ** $exponent, $exponent >= 1 ? 2 : 0, '.', '') . ' ' . $FS[$exponent];
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'md5')]
    public function md5(string $value): string
    {
        return md5($value);
    }
}
