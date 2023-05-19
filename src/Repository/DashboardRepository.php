<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\DashboardBundle\Entity\Dashboard;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardRepository extends ServiceEntityRepository
{
    private const CACHE = 3600;

    public function __construct(ManagerRegistry $managerRegistry, private readonly RoleHierarchyInterface $roleHierarchy, private readonly TokenStorageInterface $tokenStorage)
    {
        parent::__construct($managerRegistry, Dashboard::class);
    }

    /**
     * @throws AuthenticationException
     * @throws NonUniqueResultException
     */
    public function getDashboardById(int $id, bool $authentication = true): ?Dashboard
    {
        $queryBuilder = $this->createQueryBuilder('dashboard')
            ->addSelect('block')
            ->addSelect('widget')
            ->innerJoin('dashboard.blocks', 'block', Join::WITH, 'block.active = TRUE')
            ->innerJoin('block.widget', 'widget', Join::WITH, 'widget.active = TRUE')
            ->where('dashboard = :id')
            ->andWhere('dashboard.active = TRUE')
            ->orderBy('block.position', Criteria::ASC)
            ->setParameter('id', $id);

        if ($authentication) {
            $user = $this->getUser();

            $queryBuilder
                ->innerJoin('widget.privilege', 'privilege')
                ->innerJoin('privilege.groups', 'groups', Join::WITH, 'groups IN (:groups) AND groups.active = TRUE')
                ->setParameter('groups', $user->getGroups());

            if ($this->hasRoleLabel($user->getRoles())) {
                $queryBuilder
                    ->andWhere('JSON_CONTAINS(widget.permission, :permission) = TRUE')
                    ->setParameter('permission', '"ROLE_LABEL"');
            }
        }

        return $queryBuilder
            ->getQuery()
            ->useQueryCache(true)
            ->enableResultCache(self::CACHE)
            ->getOneOrNullResult();
    }

    /**
     * @throws AuthenticationException
     * @throws NonUniqueResultException
     */
    public function getDashboardByCode(string $code): ?Dashboard
    {
        $user = $this->getUser();

        $queryBuilder = $this->createQueryBuilder('dashboard')
            ->addSelect('block')
            ->addSelect('widget')
            ->innerJoin('dashboard.blocks', 'block', Join::WITH, 'block.active = TRUE')
            ->innerJoin('block.widget', 'widget', Join::WITH, 'widget.active = TRUE')
            ->innerJoin('widget.privilege', 'privilege')
            ->innerJoin('privilege.groups', 'groups', Join::WITH, 'groups IN (:groups) AND groups.active = TRUE')
            ->where('dashboard.code = :code')
            ->andWhere('dashboard.active = TRUE')
            ->orderBy('block.position')
            ->setParameter('groups', $user->getGroups())
            ->setParameter('code', $code);

        if ($this->hasRoleLabel($user->getRoles())) {
            $queryBuilder
                ->andWhere('JSON_CONTAINS(widget.permission, :permission) = TRUE')
                ->setParameter('permission', '"ROLE_LABEL"');
        }

        return $queryBuilder
            ->getQuery()
            ->useQueryCache(true)
            ->enableResultCache(self::CACHE)
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, Dashboard>
     *
     * @throws AuthenticationException
     */
    public function getDashboardDataByUser(): array
    {
        $user = $this->getUser();

        $queryBuilder = $this->createQueryBuilder('dashboard')
            ->addSelect('block')
            ->addSelect('widget')
            ->innerJoin('dashboard.blocks', 'block', Join::WITH, 'block.active = TRUE')
            ->innerJoin('block.widget', 'widget', Join::WITH, 'widget.active = TRUE')
            ->innerJoin('widget.privilege', 'privilege')
            ->innerJoin('privilege.groups', 'groups', Join::WITH, 'groups IN (:groups) AND groups.active = TRUE')
            ->innerJoin('dashboard.user', 'user')
            ->where('dashboard.active = TRUE')
            ->orderBy('IF(user = :user, 1, 0)', Criteria::DESC)
            ->addOrderBy('dashboard.dateCreated', Criteria::DESC)
            ->setParameter('groups', $user->getGroups())
            ->setParameter('user', $user);

        if ($this->hasRoleLabel($user->getRoles())) {
            $queryBuilder
                ->andWhere('JSON_CONTAINS(widget.permission, :permission) = TRUE')
                ->setParameter('permission', '"ROLE_LABEL"');
        }

        return $queryBuilder
            ->getQuery()
            ->useQueryCache(true)
            ->enableResultCache(self::CACHE)
            ->getResult();
    }

    /**
     * @return UserInterface
     *
     * @throws AuthenticationException
     */
    private function getUser(): UserInterface
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new AuthenticationException('No authentication token');
        }

        return $token->getUser();
    }

    private function hasRoleLabel(array $roles): bool
    {
        return in_array('ROLE_LABEL', $this->roleHierarchy->getReachableRoleNames($roles), true);
    }
}
