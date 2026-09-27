<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use Frontend;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Export\File;
use Runalyze\Export\Share;
use Runalyze\View\Activity\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExportController extends AbstractController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @Route("/activity/{id}/export/social/{typeid}", requirements={"id"="\d+"})
     */
    public function exporterExportAction(int $id, int $typeid, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new Frontend(true, $this->tokenStorage, $this->parameterBag);

        if (Share\Types::isValidValue($typeid)) {
            $context = new Context($id, $account->getId());
            $exporter = Share\Types::get($typeid, $context);

            if ($exporter instanceof Share\AbstractSnippetSharer) {
                $exporter->display();
            }
        }

        return new Response();
    }

    /**
     * @Route("/activity/{id}/export/file/{typeid}", requirements={"id"="\d+"})
     */
    public function fileExportAction(int $id, int $typeid, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new Frontend(true, $this->tokenStorage, $this->parameterBag);

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
