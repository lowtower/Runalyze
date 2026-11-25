<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Plugins\Statistics\Strecken\RouteNetworkPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RouteNetworkController extends AbstractController
{
    #[Route('/plugin/RunalyzePluginStat_Strecken/window.routenet.php', name: 'statistics-route-network', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(Request $request, #[CurrentUser] Account $account, RouteNetworkPresenter $presenter): Response
    {
        return $this->render('@Strecken/route_network.html.twig', $presenter->present($account, $request->query));
    }
}
