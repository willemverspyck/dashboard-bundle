<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\DashboardBundle\Entity\Activity;
use Spyck\DashboardBundle\Entity\Dashboard;
use Symfony\Component\Security\Core\User\UserInterface;

class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Activity::class);
    }

    /**
     * Store activity.
     */
    public function putActivity(UserInterface $user, Dashboard $dashboard, array $variables, string $view, int $type, array $log = null): Activity
    {
        $activity = new Activity();
        $activity->setUser($user);
        $activity->setDashboard($dashboard);
        $activity->setVariables($variables);
        $activity->setView($view);
        $activity->setType($type);
        $activity->setLog($log);

        $this->getEntityManager()->persist($activity);
        $this->getEntityManager()->flush();

        return $activity;
    }
}
