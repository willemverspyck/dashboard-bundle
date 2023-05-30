<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Spyck\DashboardBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Annotation as Serializer;

final class Field
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_NUMBER = 'number';
    public const TYPE_CURRENCY = 'currency';
    public const TYPE_POSITION = 'position';
    public const TYPE_ARRAY = 'array';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DATE = 'date';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_TEXT = 'text';
    public const TYPE_TIME = 'time';

    private ?Field $parent = null;

    #[Serializer\Groups(AbstractController::GROUPS)]
    private string $name;

    #[Serializer\Groups(AbstractController::GROUPS)]
    private Callback|string $source;

    #[Serializer\Groups(AbstractController::GROUPS)]
    private string $type;

    private ?array $typeOptions = null;

    private ?Callback $filter = null;

    /**
     * @var Collection<int, Field>
     */
    private Collection $children;

    public function __construct(string $name, Callback|string $source, string $type, array $typeOptions = null, Callback $filter = null)
    {
        $this->children = new ArrayCollection();

        $this->setName($name);
        $this->setSource($source);
        $this->setType($type);
        $this->setTypeOptions($typeOptions);
        $this->setFilter($filter);
    }

    public function setParent(?Field $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?Field
    {
        return $this->parent;
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

    public function setSource(Callback|string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): Callback|string
    {
        return $this->source;
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

    public function setTypeOptions(?array $typeOptions): static
    {
        $this->typeOptions = $typeOptions;

        return $this;
    }

    public function getTypeOptions(): ?array
    {
        return $this->typeOptions;
    }

    public function setFilter(?Callback $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function getFilter(): Callback|null
    {
        return $this->filter;
    }

    public function addChild(Field $child): static
    {
        $this->children->add($child);

        return $this;
    }

    public function removeChild(Field $child): void
    {
        $this->children->removeElement($child);
    }

    public function clearChildren(): void
    {
        $this->children->clear();
    }

    /**
     * @return Collection<int, Field>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addRoute(Route $route): static
    {
        $this->routes->add($route);

        return $this;
    }

    public function removeRoute(Route $route): void
    {
        $this->routes->removeElement($route);
    }

    public function clearRoutes(): void
    {
        $this->routes->clear();
    }

    /**
     * @return Collection<int, Route>
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }
}
