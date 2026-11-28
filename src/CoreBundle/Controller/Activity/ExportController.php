<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use Frontend;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Activity\SharedActivityUrlGenerator;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginOutputRenderer;
use Runalyze\Export\File;
use Runalyze\Export\Share;
use Runalyze\View\Activity\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

class ExportController extends AbstractController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack $requestStack,
        private readonly LegacyPluginOutputRenderer $legacyPluginOutputRenderer
    ) {}

    /**
     * @throws Throwable if the legacy exporter throws
     */
    #[Route(path: '/activity/{id}/export/social/{typeid}', requirements: [
        'id' => '\d+',
    ], methods: ['GET'])]
    public function exporterExportAction(int $id, int $typeid, Request $request, SharedActivityUrlGenerator $sharedActivityUrls, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! Share\Types::isValidValue($typeid)) {
            return new Response();
        }

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $context = new Context($id, $account->getId());
        $exporter = Share\Types::get($typeid, $context);

        if (! $exporter instanceof Share\AbstractSnippetSharer) {
            return new Response();
        }

        $activityUrl = $sharedActivityUrls->generate($context->activity()->id() ?? 0);
        $isLocalhost = $request->getHost() === 'localhost';

        return $this->legacyPluginOutputRenderer->render(function () use ($exporter, $activityUrl, $isLocalhost): void {
            $exporter->display($activityUrl, $isLocalhost);
        });
    }

    #[Route(path: '/activity/{id}/export/file/{typeid}', requirements: [
        'id' => '\d+',
    ], methods: ['GET'])]
    public function fileExportAction(int $id, int $typeid, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (File\Types::isValidValue($typeid)) {
            new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

            $context = new Context($id, $account->getId());
            $exporter = File\Types::get($typeid, $context);

            if ($exporter instanceof File\AbstractFileExporter) {
                return $exporter->toResponse();
            }
        }

        return new Response();
    }
}
