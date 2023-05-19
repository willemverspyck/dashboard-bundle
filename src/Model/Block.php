<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OpenApi;
use Symfony\Component\Serializer\Annotation as Serializer;

final class Block
{
    #[Serializer\Groups(['dashboard'])]
    private Dashboard $dashboard;

    private Widget $widget;

    #[Serializer\Groups(['dashboard'])]
    private string $name;

    #[Serializer\Groups(['dashboard'])]
    private ?string $description = null;

    #[Serializer\Groups(['dashboard'])]
    private string $descriptionEmpty;

    #[Serializer\Groups(['dashboard'])]
    private ?string $url = null;

    #[Serializer\Groups(['dashboard'])]
    private ?string $size = null;

    /**
     * @todo: Items can also be array
     */
    #[OpenApi\Property(type: 'array', items: new OpenApi\Items(type: 'string'))]
    #[Serializer\Groups(['dashboard'])]
    private array $parameters = [];

    /**
     * @var array<int, Filter>
     */
    #[OpenApi\Property(type: 'array', items: new OpenApi\Items(ref: new Model(type: Filter::class)))]
    #[Serializer\Groups(['dashboard'])]
    private array $filters = [];

    /**
     * @todo: Can be replaced with object
     */
    #[OpenApi\Property(type: 'string')]
    #[Serializer\Groups(['dashboard'])]
    private array $downloads = [];

    #[Serializer\Groups(['dashboard'])]
    private string $chart;

    /**
     * @todo: Can be replaced with object
     */
    #[OpenApi\Property(type: 'string')]
    #[Serializer\Groups(['dashboard'])]
    private array $charts = [];

    #[Serializer\Groups(['dashboard'])]
    private ?bool $filter = null;

    #[Serializer\Groups(['dashboard'])]
    private ?bool $filterView = null;

    public function setDashboard(Dashboard $dashboard): static
    {
        $this->dashboard = $dashboard;

        return $this;
    }

    public function getDashboard(): Dashboard
    {
        return $this->dashboard;
    }

    public function setWidget(Widget $widget): static
    {
        $this->widget = $widget;

        return $this;
    }

    public function getWidget(): Widget
    {
        return $this->widget;
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

    public function setDescriptionEmpty(string $descriptionEmpty): static
    {
        $this->descriptionEmpty = $descriptionEmpty;

        return $this;
    }

    public function getDescriptionEmpty(): string
    {
        return $this->descriptionEmpty;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setSize(?string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
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

    public function setFilters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array<int, Filter>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setDownloads(array $downloads): static
    {
        $this->downloads = $downloads;

        return $this;
    }

    public function getDownloads(): array
    {
        return $this->downloads;
    }

    public function setChart(string $chart): static
    {
        $this->chart = $chart;

        return $this;
    }

    public function getChart(): string
    {
        return $this->chart;
    }

    public function setCharts(array $charts): static
    {
        $this->charts = $charts;

        return $this;
    }

    public function getCharts(): array
    {
        return $this->charts;
    }

    public function setFilter(?bool $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function hasFilter(): ?bool
    {
        return $this->filter;
    }

    public function setFilterView(?bool $filterView): static
    {
        $this->filterView = $filterView;

        return $this;
    }

    public function hasFilterView(): ?bool
    {
        return $this->filterView;
    }
}
