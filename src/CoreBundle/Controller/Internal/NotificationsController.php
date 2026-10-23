<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Runalyze\Bundle\CoreBundle\Component\Notifications\MessageFactory;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/_internal/notifications')]
class NotificationsController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
        private readonly NotificationRepository $notificationRepository
    ) {}

    #[Route(path: '/read/all', name: 'internal-notifications-read-all', methods: ['GET'])]
    public function readAllNotificationsAction(?Account $account = null): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $this->notificationRepository
            ->markAllAsRead($account);

        return new JsonResponse();
    }

    #[Route(path: '/read/{id}', name: 'internal-notifications-read', methods: ['GET'])]
    public function readNotificationAction(int $id, ?Account $account = null): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $notification = $this->notificationRepository->find($id);
        if ($notification === null || $notification->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $this->notificationRepository
            ->markAsRead($notification);

        return new JsonResponse();
    }

    #[Route(path: '', name: 'internal-notifications-list', methods: ['GET'])]
    public function newNotificationsAction(Request $request, ?Account $account = null): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

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
