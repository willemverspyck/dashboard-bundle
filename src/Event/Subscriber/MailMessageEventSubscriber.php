<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Event\Subscriber;

use Exception;
use Spyck\DashboardBundle\Entity\Activity;
use Spyck\DashboardBundle\Message\MailMessageInterface;
use Spyck\DashboardBundle\Repository\ActivityRepository;
use Spyck\DashboardBundle\Repository\DashboardRepository;
use Spyck\DashboardBundle\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

final class MailMessageEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ActivityRepository $activityRepository, private readonly DashboardRepository $dashboardRepository, private readonly UserRepository $userRepository)
    {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => [
                'onMessageFailed',
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof MailMessageInterface) {
            $user = $this->userRepository->getUserById($message->getUser());

            if (null === $user) {
                return;
            }

            $dashboard = $this->dashboardRepository->getDashboardById($message->getId(), false);

            if (null === $dashboard) {
                return;
            }

            $log = [
                $event->getThrowable()->getPrevious()->getMessage(),
            ];

            $this->activityRepository->putActivity($user, $dashboard, $message->getVariables(), $message->getView(), Activity::TYPE_MAIL, $log);
        }
    }
}
