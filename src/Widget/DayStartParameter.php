<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

final class DayStartParameter extends AbstractDateParameter
{
    public function getEnvironment(): ?string
    {
        return 'PARAMETER_DAY_START';
    }

    public function getField(): string
    {
        return 'dateStart';
    }

    public function getName(): string
    {
        return 'dateStart';
    }
}
