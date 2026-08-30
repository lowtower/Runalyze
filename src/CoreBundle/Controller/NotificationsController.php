<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/notifications")
 */
class NotificationsController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    use AppServiceSubscriberTrait;

    /**
     * @return \Runalyze\Bundle\CoreBundle\Entity\NotificationRepository
     */
    protected function getNotificationsRepository()
    {
        return $this->getDoctrine()
            ->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Notification::class);
    }

    /**
     * @Route("", name="notifications-list")
     * @Security("has_role('ROLE_USER')")
     */
    public function newNotificationsAction(Account $account): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('notifications.html.twig', [
            'notifications' => $this->getNotificationsRepository()
                ->findAll($account),
            'router' => $this->get('router'),
            'translator' => $this->get('translator.default'),
        ]);
    }
}
