<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Parameter;

use Spyck\DashboardBundle\Widget\AbstractMultipleRequest;

final class MonthRangeParameter extends AbstractMultipleRequest
{
    public function __construct()
    {
        $this
            ->addChild(new MonthStartParameter())
            ->addChild(new MonthEndParameter());
    }
}
