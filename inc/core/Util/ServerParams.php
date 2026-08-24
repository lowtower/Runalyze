<?php

namespace Runalyze\Util;

class ServerParams
{
    /**
     * @return int|null [bytes]
     */
    public function getPostMaxSizeInBytes()
    {
        return $this->transformStringToBytes($this->getNormalizedIniPostMaxSize());
    }

    /**
     * @return int|null [bytes]
     */
    public function getUploadMaxFilesize()
    {
        return $this->transformStringToBytes($this->getNormalizedIniUploadMaxFilesize());
    }

    public function getNormalizedIniPostMaxSize(): string
    {
        return strtoupper(trim(ini_get('post_max_size')));
    }

    public function getNormalizedIniUploadMaxFilesize(): string
    {
        return strtoupper(trim(ini_get('upload_max_filesize')));
    }

    /**
     * @param string $iniSetting
     * @return null|int [bytes]
     */
    public function transformStringToBytes($iniSetting)
    {
        $iniMax = strtolower($iniSetting);

        if ($iniMax === '') {
            return null;
        }

        if ($iniMax === '-1') {
            return PHP_INT_MAX;
        }

        $max = ltrim($iniMax, '+');

        if (strpos($max, '0x') === 0) {
            $max = intval($max, 16);
        } elseif (strpos($max, '0') === 0) {
            $max = intval($max, 8);
        } else {
            $max = (int) $max;
        }

        switch (substr($iniMax, -1)) {
            case 't': $max *= 1024;
            case 'g': $max *= 1024;
            case 'm': $max *= 1024;
            case 'k': $max *= 1024;
        }

        return $max;
    }
}
