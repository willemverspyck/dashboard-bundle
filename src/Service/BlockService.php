<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use Spyck\DashboardBundle\Entity\Api;
use Spyck\DashboardBundle\Entity\Block;
use Spyck\DashboardBundle\Entity\Mail;
use Spyck\DashboardBundle\Entity\Module;
use Spyck\DashboardBundle\Model\Block as BlockModel;
use Spyck\DashboardBundle\Model\Filter;
use Spyck\DashboardBundle\Utility\BlockUtility;
use Spyck\DashboardBundle\View\ViewInterface;
use Spyck\DashboardBundle\Widget\EntityFilterInterface;
use Spyck\DashboardBundle\Widget\LimitFilter;
use Spyck\DashboardBundle\Widget\OffsetFilter;
use Spyck\DashboardBundle\Widget\OptionFilterInterface;
use Spyck\DashboardBundle\Widget\WidgetInterface;
use Exception;
use Symfony\Component\Routing\RouterInterface;

class BlockService
{
    public function __construct(private readonly RouterInterface $router, private readonly RepositoryService $repositoryService, private readonly WidgetService $widgetService)
    {
    }

    /**
     * @throws Exception
     */
    public function getBlockAsModel(Block $block, array $variables = [], string $view = ViewInterface::JSON, bool $preload = false): BlockModel
    {
        $blockModel = new BlockModel();

        $parameterBag = BlockUtility::getParameterBag($block, $variables);

        if ($preload) {
            $blockModel->setWidget($this->widgetService->getWidgetAsModel($block, $parameterBag->all(), $view));
        }

        $widget = $block->getWidget();

        $widgetInstance = $this->widgetService->getWidgetInstance($widget->getAdapter(), $parameterBag->all(), true);

        $blockModel->setName(null !== $block->getName() ? $block->getName() : $widget->getName());
        $blockModel->setDescription(null !== $block->getDescription() ? $block->getDescription() : $widget->getDescription());
        $blockModel->setDescriptionEmpty($widget->getDescriptionEmpty());
        $blockModel->setSize($block->getSize());
        $blockModel->setFilters($this->getBlockFilter($widgetInstance));
        $blockModel->setParameters($this->getBlockParameters($widgetInstance));
        $blockModel->setDownloads($this->getDownloads($block));
        $blockModel->setUrl($this->getBlockUrl($block, 'table'));
        $blockModel->setChart(null !== $block->getChart() ? $block->getChart() : $widget->getChart());
        $blockModel->setCharts($widget->getCharts());
        $blockModel->setFilter($block->hasFilter());
        $blockModel->setFilterView($block->hasFilterView());

        return $blockModel;
    }

    /**
     * Get filters of the widget.
     */
    private function getBlockFilter(WidgetInterface $widgetInstance): array
    {
        $type = $widgetInstance->getType();
        $typeName = $type?->getName();

        $data = [];

        foreach ($widgetInstance->getFilterData() as $filter) {
            $name = $filter->getName();

            $returnEntityData = [];

            if ($filter instanceof EntityFilterInterface) {
                $items = $filter->getDataAsObject();

                if (null === $items) {
                    $items = [];
                }

                $items = array_map(function (object $object): int {
                    return $object->getId();
                }, $items);

                $repository = $this->repositoryService->getRepository($name);

                if (null !== $repository) {
                    $entityData = $this->repositoryService->getEntityData($name);

                    foreach ($entityData as $entityRow) {
                        $parent = null;

                        if ($entityRow instanceof Api) {
                            $apiType = array_filter($entityRow->getModules()->toArray(), function (Module $module) use ($typeName): bool {
                                return $typeName === $module->getType();
                            });

                            if (0 === count($apiType)) {
                                continue;
                            }
                        }

                        if ($entityRow instanceof Module) {
                            if ($typeName !== $entityRow->getType()) {
                                continue;
                            }

                            $parent = [
                                'field' => 'apiIds',
                                'id' => $entityRow->getApi()->getId(),
                            ];
                        }

                        $returnEntityData[] = [
                            'id' => $entityRow->getId(),
                            'parent' => $parent,
                            'name' => $entityRow->getName(),
                            'select' => in_array($entityRow->getId(), $items, true),
                        ];
                    }
                }
            }

            if ($filter instanceof OptionFilterInterface) {
                $items = $filter->getData();

                if (null === $items) {
                    $items = [];
                }

                foreach ($filter->getOptions() as $optionId => $optionName) {
                    $returnEntityData[] = [
                        'id' => $optionId,
                        'parent' => null,
                        'name' => $optionName,
                        'select' => in_array($optionId, $items, true),
                    ];
                }
            }

            if (count($returnEntityData) > 0) {
                // Remove this condition when support for changing limit and offset in filters
                if (false === $filter instanceof LimitFilter && false === $filter instanceof OffsetFilter) {
                    $filterModel = new Filter();
                    $filterModel->setName(ucfirst($name));
                    $filterModel->setType($filter->getType());
                    $filterModel->setParameter($filter->getField());
                    $filterModel->setData($returnEntityData);

                    $data[] = $filterModel;
                }
            }
        }

        return $data;
    }

    /**
     * Get url of the widget.
     */
    private function getBlockUrl(Block $block, string $format): string
    {
        $widget = $block->getWidget();

        $parameters = [
            'widgetId' => $widget->getId(),
            '_format' => $format,
        ];

        return $this->router->generate('app_widget_show', $parameters);
    }

    /**
     * Get url of the widget.
     */
    private function getBlockParameters(WidgetInterface $widgetInstance): array
    {
        return array_merge($widgetInstance->getParameterDataRequest(), $widgetInstance->getFilterDataRequest());
    }

    private function getDownloads(Block $block): array
    {
        $data = [];

        $formats = array_filter(Mail::getMailViews(), function (string $format): bool {
            return ViewInterface::HTML !== $format;
        }, ARRAY_FILTER_USE_KEY);

        foreach ($formats as $format => $name) {
            $data[] = [
                'name' => $name,
                'url' => $this->getBlockUrl($block, $format),
            ];
        }

        return $data;
    }
}