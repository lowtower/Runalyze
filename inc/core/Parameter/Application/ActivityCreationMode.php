<?php

declare(strict_types=1);

/**
 * This file contains class::ActivityCreationMode
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

/**
 * Activity creation mode
 * @author Hannes Christiansen
 * @package Runalyze\Parameter\Application
 */
class ActivityCreationMode extends \Runalyze\Parameter\Select
{
    /**
     * Upload form
     * @var string
     */
    public const UPLOAD = 'upload';

    /**
     * Garmin Communicator
     * @var string
     */
    public const GARMIN = 'garmin';

    /**
     * Standard form
     * @var string
     */
    public const FORM = 'form';

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct(self::UPLOAD, [
            'options' => [
                self::UPLOAD => __('Upload form'),
                self::GARMIN => __('Garmin-Communicator'),
                self::FORM => __('Standard form'),
            ],
        ]);
    }

    /**
     * Uses upload form
     */
    public function usesUpload(): bool
    {
        return $this->value() == self::UPLOAD;
    }

    /**
     * Uses Garmin-Communicator
     */
    public function usesGarminCommunicator(): bool
    {
        return $this->value() == self::GARMIN;
    }

    /**
     * Uses standard form
     */
    public function usesForm(): bool
    {
        return $this->value() == self::FORM;
    }
}
