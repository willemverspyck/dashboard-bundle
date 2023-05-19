<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

final class WeekRangeParameter extends AbstractMultipleRequest
{
    public function __construct(bool $full = false)
    {
        $this
            ->addChild(new WeekStartParameter())
            ->addChild(new WeekEndParameter($full));
    }
}
