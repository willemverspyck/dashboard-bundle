<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Model;

use OpenApi\Attributes as OpenApi;
use Symfony\Component\Serializer\Annotation as Serializer;

final class Filter
{
    #[Serializer\Groups(['dashboard'])]
    private string $name;

    #[Serializer\Groups(['dashboard'])]
    private string $type;

    #[Serializer\Groups(['dashboard'])]
    private string $parameter;

    /**
     * @todo: This must be an object with id, name, parent (array with id, field) and select
     */
    #[OpenApi\Property(type: 'array', items: new OpenApi\Items(type: 'string'))]
    #[Serializer\Groups(['dashboard'])]
    private array $data;

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setParameter(string $parameter): static
    {
        $this->parameter = $parameter;

        return $this;
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
