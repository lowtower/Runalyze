<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Plugin;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class LegacyPluginOutputRenderer
{
    /**
     * @throws Throwable if the legacy plugin throws while rendering
     */
    public function render(Plugin $plugin): Response
    {
        ob_start();

        try {
            $plugin->display();
            $content = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return new Response($content === false ? '' : $content);
    }
}
