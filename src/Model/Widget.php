<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Model;

use Symfony\Component\Serializer\Annotation as Serializer;

final class Widget
{
    #[Serializer\Groups('widget')]
    private array $data;

    #[Serializer\Groups('widget')]
    private array $fields;

    private array $properties;

    private array $events;

    private array $filters;

    private array $parameters;

    #[Serializer\Groups('widget')]
    private ?Pagination $pagination = null;

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setFields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setProperties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setEvents(array $events): static
    {
        $this->events = $events;

        return $this;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function setFilters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
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

    public function setPagination(?Pagination $pagination): static
    {
        $this->pagination = $pagination;

        return $this;
    }

    public function getPagination(): ?Pagination
    {
        return $this->pagination;
    }
}
