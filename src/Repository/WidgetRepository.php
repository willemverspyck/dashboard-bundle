<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Spyck\DashboardBundle\Entity\UserInterface;
use Spyck\DashboardBundle\Entity\Widget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class WidgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry, private readonly AuthorizationCheckerInterface $authorizationChecker, private readonly TokenStorageInterface $tokenStorage)
    {
        parent::__construct($managerRegistry, Widget::class);
    }

    /**
     * Get widget by id.
     *
     * @throws NonUniqueResultException
     */
    public function getWidgetById(int $id): ?Widget
    {
        /** @var UserInterface $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $queryBuilder = $this->createQueryBuilder('widget')
            ->addSelect('privilege')
            ->addSelect('groups')
            ->innerJoin('widget.privilege', 'privilege')
            ->innerJoin('privilege.groups', 'groups', Join::WITH, 'groups IN (:groups) AND groups.active = TRUE')
            ->where('widget.id = :id AND widget.active = TRUE')
            ->setParameter('groups', $user->getGroups())
            ->setParameter('id', $id);

        if ($this->authorizationChecker->isGranted('ROLE_LABEL')) {
            $queryBuilder
                ->andWhere('JSON_CONTAINS(widget.permission, :permission) = TRUE')
                ->setParameter('permission', '"ROLE_LABEL"');
        }

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get widget by adapter.
     *
     * @throws NonUniqueResultException
     */
    public function getWidgetByAdapter(string $adapter): ?Widget
    {
        /** @var UserInterface $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $queryBuilder = $this->createQueryBuilder('widget')
            ->innerJoin('widget.privilege', 'privilege')
            ->innerJoin('privilege.groups', 'groups', Join::WITH, 'groups IN (:groups) AND groups.active = TRUE')
            ->where('widget.adapter = :adapter AND widget.active = TRUE')
            ->setParameter('groups', $user->getGroups())
            ->setParameter('adapter', $adapter);

        if ($this->authorizationChecker->isGranted('ROLE_LABEL')) {
            $queryBuilder
                ->andWhere('JSON_CONTAINS(widget.permission, :permission) = TRUE')
                ->setParameter('permission', '"ROLE_LABEL"');
        }

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }
}
