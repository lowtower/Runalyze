<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Plugins\Statistics\Wetter\TemperatureWindowPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TemperatureWindowController extends AbstractController
{
    #[Route('/plugin/RunalyzePluginStat_Wetter/window.php', name: 'statistics-temperature-window', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(Request $request, #[CurrentUser] Account $account, TemperatureWindowPresenter $presenter): Response
    {
        return $this->render('@Wetter/temperature_window.html.twig', $presenter->present($account, $request->query));
    }
}
