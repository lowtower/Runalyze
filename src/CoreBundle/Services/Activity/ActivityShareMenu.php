<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Activity;

use Ajax;
use Icon;
use Runalyze\Export\Share\Types;
use Runalyze\View\Activity\Context;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UrlHelper;

final readonly class ActivityShareMenu
{
    public function __construct(
        private SharedActivityUrlGenerator $activityUrls,
        private Packages $assets,
        private UrlHelper $urlHelper
    ) {}

    /**
     * @return list<string>
     */
    public function links(Context $context, Request $request): array
    {
        $links = ['<li class="with-submenu"><span class="link"><i class="fa fa-fw fa-share-alt"></i> ' . __('Share') . '</span><ul class="submenu">'];
        $activity = $context->activity();
        $publicUrl = $activity->isPublic() ? $this->activityUrls->generate($activity->id() ?? 0) : null;

        if ($publicUrl !== null) {
            $links[] = '<li><a href="' . htmlspecialchars($publicUrl) . '" target="_blank">' . Icon::$ATTACH . ' ' . __('Public link') . '</a></li>';
        }

        $socialUrl = $request->getHost() === 'localhost' ? null : $publicUrl;
        $imageUrl = $this->urlHelper->getAbsoluteUrl($this->assets->getUrl('assets/images/runalyze.png'));
        foreach (Types::getEnum() as $typeId) {
            $exporter = Types::get($typeId, $context, $socialUrl, $imageUrl);
            if (! $exporter->isPossible()) {
                continue;
            }

            $link = '<li><a href="' . htmlspecialchars($exporter->url()) . '"' . ($exporter->isExternalLink() ? ' target="_blank"' : '') . '><i class="fa fa-fw ' . $exporter->iconClass() . '"></i> ' . $exporter->name() . '</a></li>';
            $links[] = $exporter->isExternalLink() ? $link : Ajax::window($link);
        }

        $links[] = '</ul></li>';

        return $links;
    }
}
