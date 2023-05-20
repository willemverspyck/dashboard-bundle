<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use Spyck\DashboardBundle\Entity\Block;
use Spyck\DashboardBundle\Entity\Widget;
use Spyck\DashboardBundle\Exception\NotFoundException;
use Spyck\DashboardBundle\Model\Block as BlockModel;
use Spyck\DashboardBundle\Model\Dashboard as DashboardModel;
use Spyck\DashboardBundle\Model\Field;
use Spyck\DashboardBundle\Model\Pagination;
use Spyck\DashboardBundle\Model\Widget as WidgetModel;
use Spyck\DashboardBundle\Repository\WidgetRepository;
use Spyck\DashboardBundle\Utility\BlockUtility;
use Spyck\DashboardBundle\Utility\DateTimeUtility;
use Spyck\DashboardBundle\View\ViewInterface;
use Spyck\DashboardBundle\Widget\WidgetInterface;
use Spyck\DashboardBundle\Request\MultipleRequestInterface;
use Spyck\DashboardBundle\Request\RequestInterface;
use Spyck\DashboardBundle\Filter\EntityFilterInterface;
use Spyck\DashboardBundle\Filter\FilterInterface;
use Spyck\DashboardBundle\Filter\OptionFilter;
use Spyck\DashboardBundle\Parameter\EntityParameterInterface;
use Spyck\DashboardBundle\Parameter\ParameterInterface;
use DateTimeInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[Autoconfigure(bind: [
    '$parameters' => [
        'PARAMETER_DAY' => '%env(string:PARAMETER_DAY)%',
        'PARAMETER_DAY_START' => '%env(string:PARAMETER_DAY_START)%',
        'PARAMETER_DAY_END' => '%env(string:PARAMETER_DAY_END)%',
        'PARAMETER_MONTH_START' => '%env(string:PARAMETER_MONTH_START)%',
        'PARAMETER_MONTH_END' => '%env(string:PARAMETER_MONTH_END)%',
        'PARAMETER_WEEK_START' => '%env(string:PARAMETER_WEEK_START)%',
        'PARAMETER_WEEK_END' => '%env(string:PARAMETER_WEEK_END)%',
        'FILTER_LIMIT' => '%env(int:FILTER_LIMIT)%',
        'FILTER_OFFSET' => '%env(int:FILTER_OFFSET)%',
    ],
])]
class WidgetService
{
    public function __construct(private readonly ImageService $imageService, private readonly RepositoryService $repositoryService, private readonly RequestStack $requestStack, private readonly RouterInterface $router, private readonly TokenStorageInterface $tokenStorage, private readonly UrlGeneratorInterface $urlGenerator, private readonly WidgetRepository $widgetRepository, private readonly array $parameters, #[TaggedIterator(tag: 'spyck.dashboard.widget')] private readonly iterable $widgets)
    {
    }

    /**
     * Get widget by name.
     *
     * @throws Exception
     */
    public function getWidgetInstance(string $name, array $variables = [], bool $fill = false): WidgetInterface
    {
        foreach ($this->widgets->getIterator() as $widget) {
            if (get_class($widget) === $name) {
                $this->setParameters($widget, $variables, $fill);
                $this->setFilters($widget, $variables, $fill);

                return $widget;
            }
        }

        throw new Exception(sprintf('Widget "%s" not found', $name));
    }

    /**
     * @throws Exception
     */
    public function getWidgetDataById(int $id, array $variables = []): DashboardModel
    {
        $widget = $this->widgetRepository->getWidgetById($id);

        return $this->getWidgetDataByWidget($widget, $variables);
    }

    /**
     * @throws Exception
     */
    public function getWidgetDataByAdapter(string $adapter, array $variables = []): DashboardModel
    {
        $widget = $this->widgetRepository->getWidgetByAdapter($adapter);

        return $this->getWidgetDataByWidget($widget, $variables);
    }

    /**
     * @throws Exception
     */
    public function getWidgetAsModel(Block $block, array $variables, string $view): WidgetModel
    {
        $parameterBag = BlockUtility::getParameterBag($block, $variables);

        $widget = $block->getWidget();

        $widgetInstance = $this->getWidgetInstance($widget->getAdapter(), $parameterBag->all());
        $widgetInstance->setWidget($widget);
        $widgetInstance->setView($view);

        return $this->getWidgetData($widgetInstance);
    }

    /**
     * Get the data with a callback.
     *
     * @throws Exception
     */
    public function getWidgetData(WidgetInterface $widgetInstance): WidgetModel
    {
        $data = $widgetInstance->getData();
        $fields = $this->getFields($widgetInstance);

        $rowData = [];

        foreach ($data as $row) {
            $columnData = [];

            foreach ($fields as $field) {
                $columnData[] = [
                    'value' => $this->getDataValue($field, $row),
                    'routes' => $this->getDataRoute($field, $row),
                    'overlays' => $this->getDataOverlays($field, $row),
                ];
            }

            $rowData[] = [
                'fields' => $columnData,
            ];
        }

        $widgetModel = new WidgetModel();
        $widgetModel->setFields($this->getColumns($fields));
        $widgetModel->setData($rowData);
        $widgetModel->setEvents($widgetInstance->getEvents());
        $widgetModel->setProperties($widgetInstance->getProperties());
        $widgetModel->setParameters($this->getParameters($widgetInstance));
        $widgetModel->setFilters($this->getFilters($widgetInstance));
        $widgetModel->setPagination($this->getPagination($widgetInstance));

        return $widgetModel;
    }

    /**
     * @throws Exception
     *
     * @todo: setParametersAsString and setParametersAsStringForSlug for unique naming when downloading
     */
    private function getWidgetDataByWidget(?Widget $widget, array $variables = []): DashboardModel
    {
        if (null === $widget) {
            throw new NotFoundException('The widget does not exist');
        }

        $currentRequest = $this->requestStack->getCurrentRequest();

        $widgetInstance = $this->getWidgetInstance($widget->getAdapter(), $variables);
        $widgetInstance->setWidget($widget);
        $widgetInstance->setView(null === $currentRequest ? ViewInterface::JSON : $currentRequest->getRequestFormat());

        $block = new BlockModel();
        $block->setWidget($this->getWidgetData($widgetInstance));
        $block->setName($widget->getName());
        $block->setDescriptionEmpty($widget->getDescriptionEmpty());
        $block->setChart($widget->getChart());

        $user = $this->tokenStorage->getToken()?->getUser();

        if (null === $user) {
            throw new AuthenticationException('User not found');
        }

        $dashboard = new DashboardModel();
        $dashboard->setUser(null === $user->getName() ? $user->getUserIdentifier() : $user->getName());
        $dashboard->setName($widget->getName());
        $dashboard->addBlock($block);

        return $dashboard;
    }

    /**
     * Set the parameters of a widget.
     */
    private function setParameters(WidgetInterface $widget, array $variables, bool $fill = false): void
    {
        $parameters = $this->mapRequest($widget->getParameters(), function (ParameterInterface $parameter) use ($variables, $fill): void {
            $data = $this->getRequestData($parameter, $variables, $fill);

            if (null === $data) {
                if (false === $fill) {
                    throw new NotFoundHttpException(sprintf('Parameter "%s" does not exist', $parameter->getName()));
                }

                return;
            }

            $parameter->setData($data);

            if ($parameter instanceof EntityParameterInterface) {
                $queryBag = new ParameterBag();

                $request = $this->requestStack->getCurrentRequest();

                if (null !== $request) {
                    $queryBag->add($request->query->all());
                }

                $dataAsObject = $this->getEntityById($parameter->getName(), $parameter->getData());

                $parameter->setDataAsObject($dataAsObject);
                $parameter->setRequest($queryBag->has($parameter->getField()));
            }
        });

        $widget->setParameters($parameters);
    }

    /**
     * Set the filters of a widget.
     */
    private function setFilters(WidgetInterface $widget, array $variables, bool $fill = false): void
    {
        $filters = $this->mapRequest($widget->getFilters(), function (FilterInterface $filter) use ($variables, $fill): void {
            $data = $this->getRequestData($filter, $variables, $fill);

            if (null === $data) {
                return;
            }

            $filter->setData(explode(',', $data));

            if ($filter instanceof EntityFilterInterface) {
                $dataAsObject = [];

                foreach ($filter->getData() as $entityId) {
                    $entity = $this->getEntityById($filter->getName(), (int) $entityId);

                    $dataAsObject[] = $entity;
                }

                $filter->setDataAsObject($dataAsObject);
            }
        });

        $widget->setFilters($filters);
    }

    private function getRequestData(RequestInterface $request, array $variables, bool $fill): ?string
    {
        $parameterBag = new ParameterBag();
        $parameterBag->add($variables);

        $field = $request->getField();

        if ($parameterBag->has($field)) {
            return sprintf('%s', $parameterBag->get($field));
        }

        if ($fill && null !== $request->getEnvironment()) {
            $key = $request->getEnvironment();

            if (array_key_exists($key, $this->parameters)) {
                return sprintf('%s', $this->parameters[$key]);
            }
        }

        return null;
    }

    /**
     * Get the entity by id.
     *
     * @throws NotFoundHttpException
     */
    private function getEntityById(string $entityName, int $entityId): object
    {
        $entity = $this->repositoryService->getEntityById($entityName, $entityId);

        if (null === $entity) {
            throw new NotFoundHttpException(sprintf('Parameter or filter "%s" does not exist (%d)', $entityName, $entityId));
        }

        return $entity;
    }

    /**
     * Get the fields.
     */
    private function getFields(WidgetInterface $widget): array
    {
        $fields = [];

        foreach ($widget->getFields() as $field) {
            if (array_key_exists('filter', $field)) {
                $filter = call_user_func($field['filter']);

                if ($filter) {
                    $fields[] = $field;
                }
            } else {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Get the column defaults.
     */
    private function getColumns(array $fields): array
    {
        $columns = [];

        foreach ($fields as $field) {
            $routes = [];

            if (array_key_exists('route', $field)) {
                $routes[] = array_fill(0, count($field['route']), []);
            }

            $overlays = [];

            if (array_key_exists('overlays', $field)) {
                foreach ($field['overlays'] as $overlay) {
                    $overlays[] = [
                        'name' => $overlay['name'],
                        'type' => $this->getDataType($overlay),
                        'typeOptions' => $this->getDataTypeOptions($overlay),
                    ];
                }
            }

            $columns[] = [
                'name' => $field['name'],
                'type' => $this->getDataType($field),
                'typeOptions' => $this->getDataTypeOptions($field),
                'chart' => array_key_exists('chart', $field) ? $field['chart'] : [],
                'routes' => $routes,
                'overlays' => $overlays,
            ];
        }

        return $columns;
    }

    private function getParameters(WidgetInterface $widget): array
    {
        $content = [];

        $parameters = $widget->getParameterData();

        foreach ($parameters as $parameter) {
            if ($parameter instanceof EntityParameterInterface) {
                $data = $parameter->getDataAsObject();

                if (null !== $data) {
                    $content[] = [
                        'name' => $parameter->getName(),
                        'data' => [
                            $data->getName(),
                        ],
                    ];
                }
            }
        }

        return $content;
    }

    private function getFilters(WidgetInterface $widget): array
    {
        $content = [];

        $filters = $widget->getFilterData();

        foreach ($filters as $filter) {
            if ($filter instanceof EntityFilterInterface) {
                $data = $filter->getDataAsObject();

                if (null !== $data) {
                    $content[] = [
                        'name' => $filter->getName(),
                        'data' => array_map(function (object $entity): string {
                            return $entity->getName();
                        }, $data),
                    ];
                }
            }

            if ($filter instanceof OptionFilter) { // OptionFilterInterface shows limit and offset
                $data = $filter->getDataAsOptions();

                if (null !== $data) {
                    $content[] = [
                        'name' => $filter->getName(),
                        'data' => $data,
                    ];
                }
            }
        }

        return $content;
    }

    /**
     * @return array<int, RequestInterface>
     */
    private function mapRequest(array $parameters, callable $callback): array
    {
        $data = [];

        foreach ($parameters as $parameter) {
            if ($parameter instanceof MultipleRequestInterface) {
                foreach ($parameter->getChildren() as $child) {
                    $callback($child);

                    $data[get_class($child)] = $child;
                }
            } else {
                $callback($parameter);

                $data[get_class($parameter)] = $parameter;
            }
        }

        return $data;
    }

    /**
     * Get the data of the route.
     */
    private function getPagination(WidgetInterface $widget): ?Pagination
    {
        $pagination = $widget->getDataPagination();

        if (null === $pagination) {
            return null;
        }

        $name = 'app_widget_show';

        $parameters = $this->getPaginationParameters($name);

        if (null === $parameters) {
            return null;
        }

        $next = null;

        if (count($widget->getData()) >= $pagination['limit']) {
            $next = $this->urlGenerator->generate($name, array_merge($parameters, [
                'limit' => $pagination['limit'],
                'offset' => $pagination['offset'] + $pagination['limit'],
            ]));
        }

        $previous = null;

        if ($pagination['offset'] - $pagination['limit'] >= 0) {
            $previous = $this->urlGenerator->generate($name, array_merge($parameters, [
                'limit' => $pagination['limit'],
                'offset' => $pagination['offset'] - $pagination['limit'],
            ]));
        }

        $pagination = new Pagination();
        $pagination->setPrevious($previous);
        $pagination->setNext($next);

        return $pagination;
    }

    private function getPaginationParameters(string $name): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        $routeCollection = $this->router->getRouteCollection();

        $route = $routeCollection->get($name);

        if (null === $route) {
            return null;
        }

        $parameters = array_merge($request->query->all(), $request->attributes->get('_route_params', []));

        $variables = $route->compile()->getVariables();

        if (count(array_diff($variables, array_keys($parameters))) > 0) {
            return null;
        }

        return $parameters;
    }

    /**
     * Get the value of the row.
     *
     * @throws Exception
     */
    private function getDataValue(array $definition, array $data): array|bool|DateTimeInterface|float|int|string|null
    {
        if (array_key_exists('callback', $definition)) {
            if (is_array($definition['callback']) && array_key_exists('name', $definition['callback'])) {
                $parameters = array_key_exists('parameters', $definition['callback']) ? $definition['callback']['parameters'] : [];

                return call_user_func($definition['callback']['name'], $data, $parameters);
            }

            return call_user_func($definition['callback'], $data);
        }

        if (array_key_exists('source', $definition) && array_key_exists($definition['source'], $data)) {
            $value = $data[$definition['source']];

            if (null === $value) {
                return null;
            }

            return match ($this->getDataType($definition)) {
                Field::TYPE_CURRENCY, Field::TYPE_NUMBER, Field::TYPE_PERCENTAGE => (float) $value,
                Field::TYPE_DATE => $value instanceof DateTimeInterface ? $value : DateTimeUtility::getDateFromString($value),
                Field::TYPE_DATETIME => $value instanceof DateTimeInterface ? $value : DateTimeUtility::getDateTimeFromString($value),
                Field::TYPE_IMAGE => $this->getDataValueImage($definition, $value),
                Field::TYPE_TIME => $value instanceof DateTimeInterface ? $value : DateTimeUtility::getTimeFromString($value),
                default => $value,
            };
        }

        return null;
    }

    private function getDataValueImage(array $definition, string $value): ?string
    {
        if (false === array_key_exists('source', $definition) || false === array_key_exists('typeOptions', $definition) || false === array_key_exists('class', $definition['typeOptions'])) {
            return null;
        }

        $image = $this->imageService->getImage($value, $definition['source'], $definition['typeOptions']['class']);

        if (null === $image) {
            return null;
        }

        return $this->imageService->getThumbnail($image, 'app_view');
    }

    /**
     * Get the data of the route.
     */
    private function getDataRoute(array $definition, array $data = []): array
    {
        $content = [];

        if (false === array_key_exists('route', $definition)) {
            return $content;
        }

        foreach ($definition['route'] as $route) {
            if (null !== $route) {
                $routeParameters = $this->getDataRouteParameters($route['parameters'], $route['parametersFill'], $data);

                if (null !== $routeParameters) {
                    $content[] = [
                        'name' => $route['name'],
                        'url' => $this->urlGenerator->generate('app_dashboard_show', $routeParameters),
                        'callback' => $this->urlGenerator->generate('app_dashboard_item', $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL),
                    ];
                }
            }
        }

        return $content;
    }

    private function getDataRouteParameters(array $parameters, array $parametersFill, array $data): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        foreach ($parametersFill as $routeParameterKey => $routeParameterValue) {
            if (array_key_exists($routeParameterValue, $data)) {
                if (null === $data[$routeParameterValue]) {
                    return null;
                }

                $parameters[$routeParameterKey] = $data[$routeParameterValue];
            } else {
                $parameters[$routeParameterKey] = in_array($routeParameterValue, ['date', 'dateStart', 'dateEnd'], true) ? (null === $request ? [] : $request->query->get($routeParameterValue)) : $routeParameterValue;
            }
        }

        return $parameters;
    }

    /**
     * Get the overlay data of the route.
     */
    private function getDataOverlays(array $definition, array $data): array
    {
        $content = [];

        if (array_key_exists('overlays', $definition)) {
            foreach ($definition['overlays'] as $overlay) {
                $content[] = [
                    'value' => $this->getDataValue($overlay, $data),
                ];
            }
        }

        return $content;
    }

    /**
     * Get the type of the row.
     */
    private function getDataType(array $definition): string
    {
        return array_key_exists('type', $definition) ? $definition['type'] : Field::TYPE_NUMBER;
    }

    /**
     * Get the type options of the row.
     */
    private function getDataTypeOptions(array $definition): array
    {
        $data = array_key_exists('typeOptions', $definition) ? $definition['typeOptions'] : [];

        switch ($this->getDataType($definition)) {
            case Field::TYPE_CURRENCY:
            case Field::TYPE_NUMBER:
                $data['abbreviation'] = array_key_exists('abbreviation', $data) ? $data['abbreviation'] : false;

                break;
            case Field::TYPE_PERCENTAGE:
            case Field::TYPE_POSITION:
                $data['abbreviation'] = false;

                break;
        }

        switch ($this->getDataType($definition)) {
            case Field::TYPE_CURRENCY:
            case Field::TYPE_NUMBER:
            case Field::TYPE_PERCENTAGE:
            case Field::TYPE_POSITION:
                $data['condition'] = array_key_exists('condition', $data) ? $data['condition'] : null;
                $data['precision'] = array_key_exists('precision', $data) ? $data['precision'] : null;

                break;
        }

        return $data;
    }
}
