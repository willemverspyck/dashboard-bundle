<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use Exception;
use Spyck\DashboardBundle\Entity\Activity;
use Spyck\DashboardBundle\Entity\Dashboard;
use Spyck\DashboardBundle\Entity\UserInterface;
use Spyck\DashboardBundle\Repository\ActivityRepository;
use Spyck\DashboardBundle\View\ViewInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActivityService
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage, private readonly RequestStack $requestStack, private readonly ActivityRepository $activityRepository)
    {
    }

    /**
     * Store Activity.
     *
     * @throws Exception
     */
    public function putActivity(Dashboard $dashboard): Activity
    {
        /** @var UserInterface $user */
        $user = $this->tokenStorage->getToken()?->getUser();

        if (null === $user) {
            throw new Exception('User not found');
        }

        $variables = array_filter($this->requestStack->getCurrentRequest()->query->all());

        return $this->activityRepository->putActivity($user, $dashboard, $variables, ViewInterface::JSON, Activity::TYPE_API);
    }
}
