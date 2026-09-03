<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    protected function getNotificationsRepository(): NotificationRepository
    {
        return $this->getDoctrine()
            ->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Notification::class);
    }

    /**
     * @Route("", name="notifications-list")
     * @Security("has_role('ROLE_USER')")
     */
    public function newNotificationsAction(Account $account): Response
    {
        return $this->render('notifications.html.twig', [
            'notifications' => $this->getNotificationsRepository()
                ->findAll($account),
            'router' => $this->get('router'),
            'translator' => $this->translator,
        ]);
    }
}
