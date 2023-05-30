<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

use Exception;
use Spyck\DashboardBundle\Doctrine\DoctrineInterface;
use Spyck\DashboardBundle\Entity\Widget;
use Spyck\DashboardBundle\Filter\EntityFilterInterface;
use Spyck\DashboardBundle\Filter\FilterInterface;
use Spyck\DashboardBundle\Filter\LimitFilter;
use Spyck\DashboardBundle\Filter\OffsetFilter;
use Spyck\DashboardBundle\Parameter\DateParameterInterface;
use Spyck\DashboardBundle\Parameter\EntityParameterInterface;
use Spyck\DashboardBundle\Parameter\ParameterInterface;
use Spyck\DashboardBundle\Type\TypeInterface;
use Symfony\Polyfill\Intl\Icu\Exception\MethodNotImplementedException;

abstract class AbstractWidget implements WidgetInterface
{
    private const CACHE = 7200;

    private string $view;

    private Widget $widget;

    /**
     * @var array|FilterInterface[]
     */
    private array $filters = [];

    /**
     * @var array|DateParameterInterface[]|EntityParameterInterface[]
     */
    private array $parameters = [];

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return array<int, FilterInterface>
     */
    public function getFilterData(): array
    {
        return $this->filters;
    }

    public function getFilterDataRequest(): array
    {
        $content = [];

        foreach ($this->getFilterData() as $filter) {
            $content[$filter->getField()] = $filter->getData();
        }

        return $content;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array<int, ParameterInterface>
     */
    public function getParameterData(): array
    {
        return $this->parameters;
    }

    public function getParameterDataRequest(): array
    {
        $data = [];

        foreach ($this->getParameterData() as $parameter) {
            if ($parameter instanceof DateParameterInterface) {
                $data[$parameter->getField()] = $parameter->getDataForRequest();
            }

            if ($parameter instanceof EntityParameterInterface) {
                $data[$parameter->getField()] = $parameter->getData();
            }
        }

        return $data;
    }

    public function getFilter(string $name): ?array
    {
        if (array_key_exists($name, $this->filters)) {
            $filter = $this->filters[$name];

            if ($filter instanceof FilterInterface) {
                if ($filter instanceof EntityFilterInterface) {
                    return $filter->getDataAsObject();
                }

                return $filter->getData();
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getParameter(string $name): object
    {
        if (array_key_exists($name, $this->parameters)) {
            $parameter = $this->parameters[$name];

            if ($parameter instanceof DateParameterInterface) {
                return $parameter->getData();
            }

            if ($parameter instanceof EntityParameterInterface) {
                return $parameter->getDataAsObject();
            }
        }

        throw new Exception(sprintf('Parameter "%s" not found', $name));
    }

    public function getCache(): ?int
    {
        return self::CACHE;
    }

    public function getEvents(): array
    {
        return [];
    }

    /**
     * @throws MethodNotImplementedException
     */
    public function getData(): array
    {
        if ($this instanceof DoctrineInterface) {
            $queryBuilder = $this->getDataFromDoctrine();

            $pagination = $this->getDataPagination();

            if (null !== $pagination) {
                $queryBuilder
                    ->setMaxResults($pagination['limit'])
                    ->setFirstResult($pagination['offset']);
            }

            $query = $queryBuilder->getQuery();

            $cache = $this->getCache();

            if (null !== $cache) {
                $query
                    ->enableResultCache($cache);
            }

            return $query
                ->useQueryCache(true)
                ->getArrayResult();
        }

        throw new MethodNotImplementedException(__METHOD__);
    }

    public function getDataPagination(): ?array
    {
        $data = $this->getFilter(LimitFilter::class);

        if (null === $data) {
            return null;
        }

        $limit = array_shift($data);

        $data = $this->getFilter(OffsetFilter::class);

        if (null === $data) {
            return null;
        }

        $offset = array_shift($data);

        return [
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        return [];
    }

    public function getProperties(): array
    {
        return [];
    }

    public function getType(): ?TypeInterface
    {
        return null;
    }

    public function setView(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    public function getView(): string
    {
        return $this->view;
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
}
