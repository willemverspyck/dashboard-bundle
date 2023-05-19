<?php

namespace Spyck\DashboardBundle\Model;

use Spyck\DashboardBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Annotation as Serializer;

final class Response
{
    #[Serializer\Groups(groups: AbstractController::GROUPS)]
    private int $total;

    #[Serializer\Groups(groups: AbstractController::GROUPS)]
    private iterable $data;

    #[Serializer\Groups(groups: AbstractController::GROUPS)]
    #[Serializer\SerializedName('definition')]
    private Definition $fields;

    #[Serializer\Groups(groups: AbstractController::GROUPS)]
    private ?Pagination $pagination = null;

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getData(): iterable
    {
        return $this->data;
    }

    public function setData(iterable $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getFields(): Definition
    {
        return $this->fields;
    }

    public function setFields(Definition $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function getPagination(): ?Pagination
    {
        return $this->pagination;
    }

    public function setPagination(?Pagination $pagination): static
    {
        $this->pagination = $pagination;

        return $this;
    }
}
