<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Runalyze\Bundle\CoreBundle\Component\Notifications\MessageFactory;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/_internal/notifications")
 */
class NotificationsController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    private TranslatorInterface $translator;

    private RouterInterface $router;

    private NotificationRepository $notificationRepository;

    public function __construct(TranslatorInterface $translator, RouterInterface $router, NotificationRepository $notificationRepository)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * @Route("/read/all", name="internal-notifications-read-all")
     * @Security("has_role('ROLE_USER')")
     */
    public function readAllNotificationsAction(Account $account): JsonResponse
    {
        $this->notificationRepository
            ->markAllAsRead($account);

        return new JsonResponse();
    }

    /**
     * @Route("/read/{id}", name="internal-notifications-read")
     * @Security("has_role('ROLE_USER')")
     */
    public function readNotificationAction(int $id, Account $account): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);
        if ($notification === null || $notification->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $this->notificationRepository
            ->markAsRead($notification);

        return new JsonResponse();
    }

    /**
     * @Route("", name="internal-notifications-list")
     * @Security("has_role('ROLE_USER')")
     */
    public function newNotificationsAction(Request $request, Account $account): JsonResponse
    {
        $messages = [];
        $factory = new MessageFactory();
        $notifications = $this->notificationRepository
            ->findAllSince($request->query->getInt('last_request'), $account);

        foreach ($notifications as $notification) {
            $message = $factory->getMessage($notification);
            $messages[] = [
                'id' => $notification->getId(),
                'link' => $message->hasLink() ? $message->getLink($this->router) : '',
                'text' => $message->getText($this->translator),
                'size' => $message->isLinkInternal() ? $message->getWindowSizeForInternalLink() : 'external',
                'createdAt' => $notification->getCreatedAt(),
            ];
        }

        return new JsonResponse($messages);
    }
}
