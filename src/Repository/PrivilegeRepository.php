<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\DashboardBundle\Entity\Privilege;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PrivilegeRepository extends ServiceEntityRepository
{
    private const CACHE = 3600;

    public function __construct(ManagerRegistry $managerRegistry, private readonly TokenStorageInterface $tokenStorage)
    {
        parent::__construct($managerRegistry, Privilege::class);
    }

    /**
     * Get first role ordered by priority.
     *
     * @throws NonUniqueResultException
     */
    public function getPrivilege(): ?Privilege
    {
        /** @var UserInterface $user */
        $user = $this->tokenStorage->getToken()->getUser();

        return $this->createQueryBuilder('privilege')
            ->innerJoin('privilege.groups', 'groups', Join::WITH, 'groups IN (:groups) AND groups.active = TRUE')
            ->orderBy('privilege.priority')
            ->setMaxResults(1)
            ->setParameter('groups', $user->getGroups())
            ->getQuery()
            ->useQueryCache(true)
            ->enableResultCache(self::CACHE)
            ->getOneOrNullResult();
    }
}
