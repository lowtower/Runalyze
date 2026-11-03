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
     */
    public const string UPLOAD = 'upload';

    /**
     * Standard form
     */
    public const string FORM = 'form';

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct(self::UPLOAD, [
            'options' => [
                self::UPLOAD => __('Upload form'),
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
     * Uses standard form
     */
    public function usesForm(): bool
    {
        return $this->value() == self::FORM;
    }
}
