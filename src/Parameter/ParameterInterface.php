<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Parameter;

use Spyck\DashboardBundle\Request\RequestInterface;

interface ParameterInterface extends RequestInterface
{
    public function getDataAsString(bool $slug = false): ?string;

    public function setData(string $data): void;
}
