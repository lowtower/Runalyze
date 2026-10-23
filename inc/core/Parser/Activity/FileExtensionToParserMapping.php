<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity;

class FileExtensionToParserMapping
{
    /**
     * @var string[]
     */
    public const array MAPPING = [
        'csv' => FileType\Csv::class,
        'fitlog' => FileType\Fitlog::class,
        'fittemp' => FileType\Fit::class,
        'gpx' => FileType\Gpx::class,
        'hrm' => FileType\Hrm::class,
        'kml' => FileType\Kml::class,
        'logbook' => FileType\Logbook::class,
        'pwx' => FileType\Pwx::class,
        'slf' => FileType\Slf::class,
        'slf3' => FileType\Slf3::class,
        'slf4' => FileType\Slf4::class,
        'sml' => FileType\Sml::class,
        'tcx' => FileType\Tcx::class,
        'trk' => FileType\Trk::class,
        'xml' => FileType\Xml::class,
    ];

    public function getParserClassFor(string $extension): ?string
    {
        $extension = mb_strtolower($extension);

        if (array_key_exists($extension, self::MAPPING)) {
            return self::MAPPING[$extension];
        }

        return null;
    }
}
