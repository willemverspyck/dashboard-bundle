<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Spyck\DashboardBundle\Widget\WidgetInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(tags: ['spyck.repository'])]
interface RepositoryInterface
{
    public static function getName(): string;

    public function getEntityById(int $id): ?object;

    public function getEntityData(WidgetInterface $widget): array;
}
