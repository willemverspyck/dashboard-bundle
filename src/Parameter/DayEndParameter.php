<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Parameter;

final class DayEndParameter extends AbstractDateParameter
{
    public function getEnvironment(): ?string
    {
        return 'PARAMETER_DAY_END';
    }

    public function getField(): string
    {
        return 'dateEnd';
    }

    public function getName(): string
    {
        return 'dateEnd';
    }
}
