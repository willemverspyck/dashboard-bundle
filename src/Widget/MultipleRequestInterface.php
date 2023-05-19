<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

interface MultipleRequestInterface
{
    public function addChild(RequestInterface $child): static;

    /**
     * @return array<int, RequestInterface>
     */
    public function getChildren(): array;
}
