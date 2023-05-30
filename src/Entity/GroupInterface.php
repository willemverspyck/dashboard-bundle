<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Entity;

interface GroupInterface
{
    public function getId(): int|null;

    public function isActive(): bool;
}
