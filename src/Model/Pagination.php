<?php

namespace Spyck\DashboardBundle\Model;

use Spyck\DashboardBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Annotation as Serializer;

final class Pagination
{
    #[Serializer\Groups(groups: AbstractController::GROUPS)]
    private ?string $next = null;

    #[Serializer\Groups(groups: AbstractController::GROUPS)]
    private ?string $previous = null;

    public function getNext(): ?string
    {
        return $this->next;
    }

    public function setNext(?string $next): static
    {
        $this->next = $next;

        return $this;
    }

    public function getPrevious(): ?string
    {
        return $this->previous;
    }

    public function setPrevious(?string $previous): static
    {
        $this->previous = $previous;

        return $this;
    }
}
