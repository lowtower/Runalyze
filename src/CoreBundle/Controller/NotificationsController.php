<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\Symfony\Component\Routing\Attribute\Route(path: '/notifications')]
class NotificationsController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    protected function getNotificationsRepository(): NotificationRepository
    {
        return $this->doctrine
            ->getRepository(Notification::class);
    }

    /**
     * @throws AccessDeniedException
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '', name: 'notifications-list')]
    public function newNotificationsAction(?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('notifications.html.twig', [
            'notifications' => $this->getNotificationsRepository()
                ->findAllFor($account),
            'router' => $this->urlGenerator,
            'translator' => $this->translator,
        ]);
    }
}
