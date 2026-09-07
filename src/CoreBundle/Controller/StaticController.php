<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaticController extends AbstractController
{
    use AppServiceSubscriberTrait;

    /**
     * @Route("/dashboard/help", name="help")
     * @Security("has_role('ROLE_USER')")
     */
    public function dashboardHelpAction(): Response
    {
        return $this->render('static/help.html.twig', [
            'version' => $this->getParameter('RUNALYZE_VERSION'),
        ]);
    }

    /**
     * @Route("/dashboard/help-calculations", name="help-calculations")
     * @Security("has_role('ROLE_USER')")
     */
    public function dashboardHelpCalculationsAction(): Response
    {
        return $this->render('static/help_calculations.html.twig');
    }
}
