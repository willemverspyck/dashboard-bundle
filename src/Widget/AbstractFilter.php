<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

abstract class AbstractFilter implements FilterInterface
{
    private ?array $data = null;

    private string $type;

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getEnvironment(): ?string
    {
        return null;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
