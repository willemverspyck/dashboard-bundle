<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Filter;

use Spyck\DashboardBundle\Request\AbstractMultipleRequest;

final class PaginationFilter extends AbstractMultipleRequest
{
    public function __construct()
    {
        $this
            ->addChild(new LimitFilter())
            ->addChild(new OffsetFilter());
    }
}
