<?php

/**
 * This file contains class::Facebook
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

/**
 * Exporter for: Facebook
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Export\Share
 */
class Facebook extends AbstractSocialSharer
{
    public static string $APP_ID = '473795412675725';

    public function iconClass(): string
    {
        return 'fab fa-facebook color-facebook';
    }

    public function isPossible(): bool
    {
        return $this->publicActivityUrl !== null;
    }

    public function url(): string
    {
        $url = $this->publicActivityUrl . '?utm_medium=referral&utm_source=facebook';

        return 'https://www.facebook.com/dialog/feed?' . http_build_query([
            'app_id' => self::$APP_ID,
            'link' => $url,
            'picture' => $this->shareImageUrl,
            'name' => $this->Context->dataview()->titleWithComment(),
            'caption' => $url,
            'description' => $this->text(),
            'redirect_uri' => 'http://www.facebook.com',
        ]);
    }

    public function name(): string
    {
        return __('Facebook');
    }

    /**
     * Get meta title
     */
    public function metaTitle(): string
    {
        return $this->text();
    }
}
