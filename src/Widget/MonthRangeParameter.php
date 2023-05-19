<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

final class MonthRangeParameter extends AbstractMultipleRequest
{
    public function __construct()
    {
        $this
            ->addChild(new MonthStartParameter())
            ->addChild(new MonthEndParameter());
    }
}
