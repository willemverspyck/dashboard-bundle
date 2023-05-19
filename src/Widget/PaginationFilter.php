<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

final class PaginationFilter extends AbstractMultipleRequest
{
    public function __construct()
    {
        $this
            ->addChild(new LimitFilter())
            ->addChild(new OffsetFilter());
    }
}
