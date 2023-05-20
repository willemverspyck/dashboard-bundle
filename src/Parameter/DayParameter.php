<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Parameter;

final class DayParameter extends AbstractDateParameter
{
    public function getEnvironment(): ?string
    {
        return 'PARAMETER_DAY';
    }

    public function getField(): string
    {
        return 'date';
    }

    public function getName(): string
    {
        return 'date';
    }
}
