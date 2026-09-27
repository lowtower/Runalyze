<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/notifications")
 */
class NotificationsController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    protected function getNotificationsRepository(): NotificationRepository
    {
        return $this->getDoctrine()
            ->getRepository(Notification::class);
    }

    /**
     * @Route("", name="notifications-list")
     * @throws AccessDeniedException
     */
    public function newNotificationsAction(Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('notifications.html.twig', [
            'notifications' => $this->getNotificationsRepository()
                ->findAllFor($account),
            'router' => $this->get('router'),
            'translator' => $this->translator,
        ]);
    }
}
