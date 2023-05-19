<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

use Spyck\DashboardBundle\Entity\Module;

final class ViewType implements TypeInterface
{
    public function getName(): string
    {
        return Module::TYPE_VIEW;
    }
}
