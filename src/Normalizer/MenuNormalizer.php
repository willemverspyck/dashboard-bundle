<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Normalizer;

use Spyck\DashboardBundle\Entity\Menu;
use Spyck\DashboardBundle\Service\DashboardService;

final class MenuNormalizer extends AbstractNormalizer
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $this->setNormalized($object, $context);

        $dashboard = null;

        if (null !== $object->getDashboard()) {
            $dashboard = $this->dashboardService->getDashboardRoute($object->getDashboard(), $object->getVariables())->toArray();
        }

        $data = $this->normalizer->normalize($object, $format, $context);
        $data['dashboard'] = $dashboard;

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        if ($this->hasNormalized($data, $context)) {
            return false;
        }

        return $data instanceof Menu;
    }
}
