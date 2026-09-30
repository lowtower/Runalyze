<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use Frontend;
use Runalyze\Bundle\CoreBundle\Controller\LegacyOutputCaptureTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Export\File;
use Runalyze\Export\Share;
use Runalyze\View\Activity\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

class ExportController extends AbstractController
{
    use LegacyOutputCaptureTrait;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @Route("/activity/{id}/export/social/{typeid}", requirements={"id"="\d+"})
     *
     * @throws Throwable if the legacy exporter throws
     */
    public function exporterExportAction(int $id, int $typeid, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(true, $this->tokenStorage, $this->parameterBag);

        if (! Share\Types::isValidValue($typeid)) {
            return new Response();
        }

        $context = new Context($id, $account->getId());
        $exporter = Share\Types::get($typeid, $context);

        if (! $exporter instanceof Share\AbstractSnippetSharer) {
            return new Response();
        }

        return $this->captureLegacyOutput(function () use ($exporter): void {
            $exporter->display();
        });
    }

    /**
     * @Route("/activity/{id}/export/file/{typeid}", requirements={"id"="\d+"})
     */
    public function fileExportAction(int $id, int $typeid, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(true, $this->tokenStorage, $this->parameterBag);

        if (File\Types::isValidValue($typeid)) {
            $context = new Context($id, $account->getId());
            $exporter = File\Types::get($typeid, $context);

            if ($exporter instanceof File\AbstractFileExporter) {
                $exporter->downloadFile();
                exit;
            }
        }

        return new Response();
    }
}
