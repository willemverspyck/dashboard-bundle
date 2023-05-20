<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Parameter;

final class MonthStartParameter extends AbstractDateParameter
{
    public function getEnvironment(): ?string
    {
        return 'PARAMETER_MONTH_START';
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

        return $data?->format('Ym');
    }
}
