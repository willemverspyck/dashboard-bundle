<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Normalizer;

use Spyck\DashboardBundle\Entity\Dashboard;
use Spyck\DashboardBundle\Service\DashboardService;

final class DashboardNormalizer extends AbstractNormalizer
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return $this->dashboardService->getDashboardRoute($object)->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Dashboard;
    }
}
