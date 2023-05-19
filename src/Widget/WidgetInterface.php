<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

use Spyck\DashboardBundle\Entity\Widget;
use Spyck\DashboardBundle\Model\Field;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(tags: ['spyck.dashboard.widget'])]
interface WidgetInterface
{
    public function getCache(): ?int;

    public function getData(): array;

    public function getEvents(): array;

    /**
     * @return array|ArrayCollection<int, Field>
     */
    public function getFields(): array|ArrayCollection;

    /**
     * @return array<RequestInterface|MultipleRequestInterface>
     */
    public function getFilters(): array;

    /**
     * @return array<RequestInterface|MultipleRequestInterface>
     */
    public function getParameters(): array;

    public function getProperties(): array;

    public function getType(): ?TypeInterface;

    public function setView(string $view): static;

    public function getView(): string;

    public function setWidget(Widget $widget): static;

    public function getWidget(): Widget;
}
