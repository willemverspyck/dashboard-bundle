<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Model;

final class DashboardRoute
{
    private string $name;

    private string $url;

    private string $callback;

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setCallback(string $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    public function getCallback(): string
    {
        return $this->callback;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'url' => $this->getUrl(),
            'callback' => $this->getCallback(),
        ];
    }
}
