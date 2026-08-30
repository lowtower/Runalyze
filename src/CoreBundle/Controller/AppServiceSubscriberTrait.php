<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Availability;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\FileHandler;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Activity\AgeGradeLookup;
use Runalyze\Bundle\CoreBundle\Services\Activity\DataSeriesRemover;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityCache;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;
use Runalyze\Bundle\CoreBundle\Services\PrivacyGuard;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Service\ElevationCorrection\Strategy\Geonames;
use Runalyze\Service\ElevationCorrection\Strategy\GeoTiff;
use Runalyze\Service\ElevationCorrection\Strategy\GoogleMaps;
use Runalyze\View\Activity\Feed;

trait AppServiceSubscriberTrait
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'app.activity.feed' => '?' . Feed::class,
            'app.activity_context.cache' => '?' . ActivityCache::class,
            'app.activity_context.factory' => '?' . ActivityContextFactory::class,
            'app.activity_context_adapter_factory' => '?' . ActivityContextAdapterFactory::class,
            'app.activity_data_container.converter' => '?' . ActivityDataContainerToActivityContextConverter::class,
            'app.activity_data_container.filter' => '?' . ActivityDataContainerFilter::class,
            'app.activity_duplicate_finder' => '?' . DuplicateFinder::class,
            'app.age_grade_lookup' => '?' . AgeGradeLookup::class,
            'app.automatic_reload_flag_setter' => '?' . AutomaticReloadFlagSetter::class,
            'app.configuration_manager' => '?' . ConfigurationManager::class,
            'app.configuration_updater' => '?' . ConfigurationUpdater::class,
            'app.data_series_remover' => '?' . DataSeriesRemover::class,
            'app.elevation_correction' => '?' . ElevationCorrection::class,
            'app.elevation_correction.geonames' => '?' . Geonames::class,
            'app.elevation_correction.geotiff' => '?' . GeoTiff::class,
            'app.elevation_correction.google_maps' => '?' . GoogleMaps::class,
            'app.file_importer' => '?' . FileImporter::class,
            'app.legacy_cache' => '?' . LegacyCache::class,
            'app.mailer.account' => '?' . AccountMailer::class,
            'app.poster.availability' => '?' . Availability::class,
            'app.poster.filehandler' => '?' . FileHandler::class,
            'app.privacy_guard' => '?' . PrivacyGuard::class,
            'app.sport_selection_factory' => '?' . SportSelectionFactory::class,
            'app.weather_forecast' => '?' . WeatherForecast::class,
        ]);
    }
}
