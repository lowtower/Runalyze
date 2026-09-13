<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
            ->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Notification::class);
    }

    /**
     * @Route("", name="notifications-list")
     */
    public function newNotificationsAction(Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('notifications.html.twig', [
            'notifications' => $this->getNotificationsRepository()
                ->findAll($account),
            'router' => $this->get('router'),
            'translator' => $this->translator,
        ]);
    }
}
