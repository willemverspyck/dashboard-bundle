<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Filter;

final class OffsetFilter extends AbstractOptionFilter
{
    public function __construct()
    {
        $this->setType(FilterInterface::TYPE_INPUT);
    }

    public function getEnvironment(): ?string
    {
        return 'FILTER_OFFSET';
    }

    public function getField(): string
    {
        return 'offset';
    }

    public function getName(): string
    {
        return 'offset';
    }
}
