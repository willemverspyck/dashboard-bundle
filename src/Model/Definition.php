<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Spyck\DashboardBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Annotation as Serializer;

final class Definition
{
    #[Serializer\Groups(AbstractController::GROUPS)]
    private string $name;

    #[Serializer\Groups(AbstractController::GROUPS)]
    private ?string $description = null;

    private array $parameters = [];

    /**
     * @var ArrayCollection<int, Field>
     */
    #[Serializer\Groups(AbstractController::GROUPS)]
    private ArrayCollection $fields;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

    public function addField(Field $field): static
    {
        $this->fields->add($field);

        return $this;
    }

    public function removeField(Field $field): void
    {
        $this->fields->removeElement($field);
    }

    public function clearFields(): void
    {
        $this->fields->clear();
    }

    /**
     * @return ArrayCollection<int, Field>
     */
    public function getFields(): ArrayCollection
    {
        return $this->fields;
    }
}
