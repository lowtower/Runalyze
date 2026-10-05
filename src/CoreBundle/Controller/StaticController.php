<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class StaticController extends AbstractController
{
    use AppServiceSubscriberTrait;

    /**
     * @throws AccessDeniedException
     */
    #[Route(path: '/dashboard/help', name: 'help')]
    public function dashboardHelpAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('static/help.html.twig', [
            'version' => $this->getParameter('RUNALYZE_VERSION'),
        ]);
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(path: '/dashboard/help-calculations', name: 'help-calculations')]
    public function dashboardHelpCalculationsAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('static/help_calculations.html.twig');
    }
}
