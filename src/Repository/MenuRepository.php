<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\DashboardBundle\Entity\Dashboard;
use Spyck\DashboardBundle\Entity\Menu;
use Spyck\DashboardBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry, private readonly AuthorizationCheckerInterface $authorizationChecker, private readonly TokenStorageInterface $tokenStorage)
    {
        parent::__construct($managerRegistry, Menu::class);
    }

    /**
     * Get menu data.
     */
    public function getMenuData(): QueryBuilder
    {
        /** @var UserInterface $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $expr = new Expr();

        $queryBuilder = $this->createQueryBuilder('menu')
            ->addSelect('menuChildren')
            ->addSelect('COUNT(menuChildren) AS HIDDEN menuChildrenCount')
            ->leftJoin('menu.children', 'menuChildren', Join::WITH, $expr->andX($expr->eq('menuChildren.active', 'TRUE'), $expr->orX($expr->isNull('menuChildren.dashboard'), $expr->in('menuChildren.dashboard', $this->getMenuDashboard('1')->getDQL()))))
            ->where('menu.parent IS NULL')
            ->andWhere('menu.active = TRUE')
            ->andWhere($expr->orX($expr->isNull('menu.dashboard'), $expr->in('menu.dashboard', $this->getMenuDashboard('2')->getDQL())))
            ->groupBy('menu')
            ->addGroupBy('menuChildren')
            ->having('(menuChildrenCount > 0 AND menu.dashboard IS NULL) OR (menuChildrenCount = 0 AND menu.dashboard IS NOT NULL)')
            ->orderBy('menu.position')
            ->addOrderBy('menuChildren.position')
            ->setParameter('groups', $user->getGroups());

        if ($this->authorizationChecker->isGranted('ROLE_COMPANY')) {
            $queryBuilder
                ->setParameter('permission', '"ROLE_COMPANY"');
        }

        if ($this->authorizationChecker->isGranted('ROLE_LABEL')) {
            $queryBuilder
                ->setParameter('permission', '"ROLE_LABEL"');
        }

        return $queryBuilder;
    }

    private function getMenuDashboard(string $index): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(sprintf('dashboard_%s', $index))
            ->from(Dashboard::class, sprintf('dashboard_%s', $index))
            ->innerJoin(sprintf('dashboard_%s.blocks', $index), sprintf('block_%s', $index), Join::WITH, sprintf('block_%s.active = TRUE', $index))
            ->innerJoin(sprintf('block_%s.widget', $index), sprintf('widget_%s', $index), Join::WITH, sprintf('widget_%s.active = TRUE', $index))
            ->innerJoin(sprintf('widget_%s.privilege', $index), sprintf('privilege_%s', $index))
            ->innerJoin(sprintf('privilege_%s.groups', $index), sprintf('groups_%s', $index), Join::WITH, sprintf('groups_%s IN (:groups) AND groups_%s.active = TRUE', $index, $index))
            ->groupBy(sprintf('dashboard_%s', $index));

        if ($this->authorizationChecker->isGranted('ROLE_COMPANY') || $this->authorizationChecker->isGranted('ROLE_LABEL')) {
            $queryBuilder
                ->andWhere(sprintf('JSON_CONTAINS(widget_%s.permission, :permission) = TRUE', $index));
        }

        return $queryBuilder;
    }
}
