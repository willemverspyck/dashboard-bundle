<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

interface OptionFilterInterface extends FilterInterface
{
    public function getDataAsOptions(): ?array;

    public function getOptions(): array;

    public function setOptions(array $options): void;
}
