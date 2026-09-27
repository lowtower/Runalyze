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
     * @Route("/dashboard/help", name="help")
     * @throws AccessDeniedException
     */
    public function dashboardHelpAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('static/help.html.twig', [
            'version' => $this->getParameter('RUNALYZE_VERSION'),
        ]);
    }

    /**
     * @Route("/dashboard/help-calculations", name="help-calculations")
     * @throws AccessDeniedException
     */
    public function dashboardHelpCalculationsAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('static/help_calculations.html.twig');
    }
}
