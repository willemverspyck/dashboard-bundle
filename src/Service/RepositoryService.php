<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use App\Entity\AggregateModule;
use Spyck\DashboardBundle\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RepositoryService
{
    public function __construct(#[TaggedIterator(tag: 'spyck.repository', defaultIndexMethod: 'getName')] private readonly iterable $repositories)
    {
    }

    /**
     * Get repository by name.
     */
    public function getRepository(string $name): ?RepositoryInterface
    {
        foreach ($this->repositories->getIterator() as $index => $repository) {
            if ($index === $name) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * @return iterable<int, RepositoryInterface>
     */
    public function getRepositories(): iterable
    {
        return $this->repositories->getIterator();
    }

    /**
     * Get the entity by id.
     *
     * @throws NotFoundHttpException
     */
    public function getEntityById(string $entityName, int $entityId): ?object
    {
        $repository = $this->getRepository($entityName);

        if (null === $repository) {
            throw new NotFoundHttpException(sprintf('Service "%s" does not exist (%d)', $entityName, $entityId));
        }

        return call_user_func([$repository, sprintf('get%sById', ucfirst($entityName))], $entityId);
    }

    /**
     * Get the entity data.
     *
     * @throws NotFoundHttpException
     */
    public function getEntityData(string $entityName): array
    {
        $repository = $this->getRepository($entityName);

        if (null === $repository) {
            throw new NotFoundHttpException(sprintf('Service "%s" does not exist', $entityName));
        }

        return call_user_func([$repository, sprintf('get%sData', ucfirst($entityName))], AggregateModule::class); // Module::class moet variable
    }
}
