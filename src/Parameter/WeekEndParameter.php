<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Parameter;

final class WeekEndParameter extends AbstractDateParameter
{
    public function __construct(private readonly bool $full = false)
    {
    }

    public function getEnvironment(): ?string
    {
        return 'PARAMETER_WEEK_END';
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

        if (null === $data) {
            return null;
        }

        if ($this->full) {
            $data->modify('Last Sunday');
        }

        return $data->format('Y-m-d');
    }
}
