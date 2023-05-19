<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

final class MonthEndParameter extends AbstractDateParameter
{
    public function getEnvironment(): ?string
    {
        return 'PARAMETER_MONTH_END';
    }

    public function getField(): string
    {
        return 'dateEnd';
    }

    public function getName(): string
    {
        return 'dateEnd';
    }

    public function getDataForQueryBuilder(): ?string
    {
        $data = $this->getData();

        return $data?->format('Ym');
    }
}
