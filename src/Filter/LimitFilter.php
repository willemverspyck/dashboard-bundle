<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Filter;

final class LimitFilter extends AbstractOptionFilter
{
    public function __construct()
    {
        $this->setType(FilterInterface::TYPE_INPUT);
    }

    public function getEnvironment(): ?string
    {
        return 'FILTER_LIMIT';
    }

    public function getField(): string
    {
        return 'limit';
    }

    public function getName(): string
    {
        return 'limit';
    }
}
