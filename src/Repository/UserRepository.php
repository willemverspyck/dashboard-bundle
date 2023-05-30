<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\DashboardBundle\Entity\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry, #[Autowire('%spyck.dashboard.user.class%')] private readonly string $class)
    {
        parent::__construct($managerRegistry, $this->class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getUserById(int $id): ?UserInterface
    {
        return $this->createQueryBuilder('user')
            ->addSelect('groups')
            ->addSelect('privilege')
            ->leftJoin('user.groups', 'groups', Join::WITH, 'groups.active = TRUE')
            ->leftJoin('groups.privileges', 'privilege')
            ->where('user.id = :id')
            ->andWhere('user.active = TRUE')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
