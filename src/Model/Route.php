<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Model;

final class Route
{
    private string $name;

    private array $parameters;

    public function __construct(string $name, array $parameters = [])
    {
        $this->setName($name);
        $this->setParameters($parameters);
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
