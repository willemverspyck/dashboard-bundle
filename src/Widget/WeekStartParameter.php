<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

final class WeekStartParameter extends AbstractDateParameter
{
    public function getEnvironment(): ?string
    {
        return 'PARAMETER_WEEK_START';
    }

    public function getField(): string
    {
        return 'dateStart';
    }

    public function getName(): string
    {
        return 'dateStart';
    }

    public function getDataForQueryBuilder(): ?string
    {
        $data = $this->getData();

        return $data?->format('Y-m-d');
    }
}
