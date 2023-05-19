<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Spyck\DashboardBundle\Entity\Dashboard;
use Spyck\DashboardBundle\Entity\Favorite;
use Spyck\DashboardBundle\Entity\UserInterface;

class FavoriteRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Favorite::class);
    }

    public function putFavorite(UserInterface $user, Dashboard $dashboard): Favorite
    {
        $favorite = new Favorite();
        $favorite->setUser($user);
        $favorite->setDashboard($dashboard);

        $this->getEntityManager()->persist($favorite);
        $this->getEntityManager()->flush();
    }
}
