<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(tags: ['spyck.repository'])]
interface RepositoryInterface
{
    public static function getName(): string;
}
