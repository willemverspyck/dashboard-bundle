<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use Spyck\DashboardBundle\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RepositoryService
{
    public function __construct(#[TaggedIterator(tag: 'spyck.repository', defaultIndexMethod: 'getName')] private readonly iterable $repositories)
    {
    }

    public function getRepository(string $name): RepositoryInterface
    {
        foreach ($this->getRepositories() as $index => $repository) {
            if ($index === $name) {
                return $repository;
            }
        }

        throw new NotFoundHttpException(sprintf('Repository "%s" does not exist', $name));
    }

    /**
     * @return iterable<string, RepositoryInterface>
     */
    public function getRepositories(): iterable
    {
        return $this->repositories->getIterator();
    }

    /**
     * @throws NotFoundHttpException
     */
    public function getEntityById(string $entityName, int $entityId): ?object
    {
        return $this->getRepository($entityName)->getEntityById($entityId);
    }
}
