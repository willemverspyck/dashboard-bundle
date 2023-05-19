<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

interface RequestInterface
{
    public function getEnvironment(): ?string;

    public function getField(): string;

    public function getName(): string;
}
