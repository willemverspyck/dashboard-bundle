<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use Spyck\DashboardBundle\Entity\Dashboard;
use Spyck\DashboardBundle\Entity\Mail;
use Spyck\DashboardBundle\Model\Dashboard as DashboardModel;
use Spyck\DashboardBundle\Model\DashboardRoute;
use Spyck\DashboardBundle\Repository\DashboardRepository;
use Spyck\DashboardBundle\Repository\PrivilegeRepository;
use Spyck\DashboardBundle\Utility\BlockUtility;
use Spyck\DashboardBundle\View\ViewInterface;
use Spyck\DashboardBundle\Request\RequestInterface;
use Spyck\DashboardBundle\Parameter\DateParameterInterface;
use Spyck\DashboardBundle\Parameter\DayRangeParameter;
use Spyck\DashboardBundle\Parameter\EntityParameterInterface;
use Spyck\DashboardBundle\Parameter\MonthRangeParameter;
use Spyck\DashboardBundle\Parameter\ParameterInterface;
use Spyck\DashboardBundle\Parameter\WeekRangeParameter;
use Exception;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class DashboardService
{
    public function __construct(private readonly BlockService $blockService, private readonly DashboardRepository $dashboardRepository, private readonly PrivilegeRepository $privilegeRepository, private readonly RouterInterface $router, private readonly TokenStorageInterface $tokenStorage, private readonly WidgetService $widgetService)
    {
    }

    /**
     * @throws Exception
     */
    public function getDashboardAsModel(Dashboard $dashboard, array $variables = [], string $view = ViewInterface::JSON, bool $preload = false): DashboardModel
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (null === $user) {
            throw new AuthenticationException('User not found');
        }

        $variables = $this->filterVariables($dashboard, $variables);

        $dashboardRoute = $this->getDashboardRoute($dashboard, $variables);

        $dashboardModel = new DashboardModel();

        $dashboardModel
            ->setUser(null === $user->getName() ? $user->getUserIdentifier() : $user->getName())
            ->setName($dashboard->getName())
            ->setDescription($dashboard->getDescription())
            ->setUrl($dashboardRoute->getUrl())
            ->setCallback($dashboardRoute->getCallback())
            ->setParameters($this->getDashboardParameters($dashboard, $variables))
            ->setParametersAsString($this->getDashboardParametersAsString($dashboard, $variables))
            ->setParametersAsStringForSlug($this->getDashboardParametersAsString($dashboard, $variables, true))
            ->setDownloads($this->getDownloads($dashboard, $variables));

        foreach ($dashboard->getBlocks() as $block) {
            $blockAsModel = $this->blockService->getBlockAsModel($block, $variables, $view, $preload);

            $dashboardModel->addBlock($blockAsModel);
        }

        return $dashboardModel;
    }

    /**
     * Get required parameters for dashboard.
     *
     * @return array<int, ParameterInterface>
     *
     * @throws Exception
     */
    public function getDashboardParameterData(Dashboard $dashboard, array $variables = []): array
    {
        $data = [];

        foreach ($dashboard->getBlocks() as $block) {
            $parameterBag = BlockUtility::getParameterBag($block, $variables);

            $widgetInstance = $this->widgetService->getWidgetInstance($block->getWidget()->getAdapter(), $parameterBag->all(), true);

            foreach ($widgetInstance->getParameterData() as $parameter) {
                $name = $parameter->getName();

                if (false === array_key_exists($name, $data)) {
                    $data[$name] = $parameter;
                }
            }
        }

        return array_values($data);
    }

    /**
     * @return array<string, string>
     *
     * @throws Exception
     */
    public function getDashboardParameters(Dashboard $dashboard, array $variables): array
    {
        $data = [];

        foreach ($this->getDashboardParameterData($dashboard, $variables) as $parameter) {
            $reflectionClass = new ReflectionClass($parameter);

            $name = $reflectionClass->getShortName();

            if ($parameter instanceof DateParameterInterface) {
                $data[$name] = $parameter->getDataForRequest();
            }

            if ($parameter instanceof EntityParameterInterface) {
                if ($parameter->isRequest()) {
                    $data[$name] = $parameter;
                }
            }
        }

        return $data;
    }

    /**
     * @return array<string, string>
     *
     * @throws Exception
     *
     * @todo This function can be made easier
     */
    public function getDashboardParametersAsString(Dashboard $dashboard, array $variables, bool $slug = false): array
    {
        $data = [];

        $childrenExclude = [];

        $hasMultipleRequest = false;

        $parameters = $this->getDashboardParameterData($dashboard, $variables);

        foreach ([new DayRangeParameter(), new MonthRangeParameter(), new WeekRangeParameter()] as $multipleRequest) {
            $children = $multipleRequest->getChildren();

            $intersect = array_uintersect($parameters, $children, function (RequestInterface $a, RequestInterface $b): int {
                if (get_class($a) === get_class($b)) {
                    return 0;
                }

                return get_class($a) > get_class($b) ? 1 : -1;
            });

            if (count($intersect) === count($children)) {
                $childrenExclude = array_merge($childrenExclude, $children);

                if (false === $hasMultipleRequest) {
                    $range = [];

                    foreach ($intersect as $intersects) {
                        $range[] = $intersects->getDataAsString($slug);
                    }

                    $reflectionClass = new ReflectionClass($multipleRequest);

                    $name = $reflectionClass->getShortName();

                    $data[$name] = implode(' - ', $range);

                    $hasMultipleRequest = true;
                }
            }
        }

        $difference = array_udiff($parameters, $childrenExclude, function (RequestInterface $a, RequestInterface $b): int {
            if (get_class($a) === get_class($b)) {
                return 0;
            }

            return get_class($a) > get_class($b) ? 1 : -1;
        });

        foreach ($difference as $name => $parameter) {
            $data[$name] = $parameter->getDataAsString($slug);
        }

        /*
         * If there is a dateRange object, put it at the end of the array
         */
        if ($hasMultipleRequest) {
            $data = array_slice($data, 1) + array_slice($data, 0, 1);
        }

        return array_filter($data);
    }

    /**
     * Check the dashboard parameters if their missing.
     *
     * @throws Exception
     */
    public function checkDashboardParameterData(Dashboard $dashboard, array $variables = []): ?array
    {
        $returnData = [];

        $parameters = $this->getDashboardParameterData($dashboard, $variables);

        foreach ($parameters as $parameter) {
            if ($parameter instanceof EntityParameterInterface) {
                $data = $parameter->getData();

                if (null === $data) {
                    $returnData[$parameter->getName()] = [
                        'url' => $this->router->generate($parameter->getRoute()),
                        'parameters' => $variables, // @todo: Change parameters in variables in JS
                        'field' => $parameter->getField(),
                    ];
                }
            }
        }

        if (0 === count($returnData)) {
            return null;
        }

        return $returnData;
    }

    public function getDashboardRoute(Dashboard $dashboard, array $variables = []): DashboardRoute
    {
        $variables['dashboardId'] = $dashboard->getId();

        $dashboardRequest = new DashboardRoute();

        return $dashboardRequest
            ->setName($dashboard->getName())
            ->setUrl($this->router->generate('app_dashboard_show', $variables))
            ->setCallback($this->router->generate('app_dashboard_item', $variables, UrlGeneratorInterface::ABSOLUTE_URL));
    }

    /**
     * @throws NotFoundHttpException
     */
    public function getDashboardRouteOfPrivilege(): ?DashboardRoute
    {
        $role = $this->privilegeRepository->getPrivilege();

        if (null === $role) {
            return null;
        }

        $dashboard = $role->getDashboard();

        if (null === $dashboard) {
            throw new NotFoundHttpException('No default dashboard');
        }

        return $this->getDashboardRoute($dashboard);
    }

    /**
     * @throws Exception
     */
    public function getDashboardRouteByCode(string $code, array $variables): ?DashboardRoute
    {
        $dashboard = $this->dashboardRepository->getDashboardByCode($code);

        if (null === $dashboard) {
            return null;
        }

        return $this->getDashboardRoute($dashboard, $variables);
    }

    /**
     * Remove variables that are not part of the dashboard.
     *
     * @throws Exception
     */
    private function filterVariables(Dashboard $dashboard, array $variables = []): array
    {
        $data = [];

        foreach ($dashboard->getBlocks() as $block) {
            $parameterBag = BlockUtility::getParameterBag($block, $variables);

            $widgetInstance = $this->widgetService->getWidgetInstance($block->getWidget()->getAdapter(), $parameterBag->all(), true);

            $data = array_replace($data, $widgetInstance->getParameterDataRequest(), $widgetInstance->getFilterDataRequest());
        }

        return array_intersect_key($variables, $data);
    }

    private function getDownloads(Dashboard $dashboard, array $variables): array
    {
        $data = [];

        $formats = Mail::getMailViews();

        foreach ($formats as $format => $name) {
            $data[] = [
                'id' => $format,
                'name' => $name,
            ];
        }

        return [
            'url' => $this->router->generate('app_dashboard_mail', [
                'dashboardId' => $dashboard->getId(),
            ]),
            'fields' => [
                'view' => [
                    'data' => $data,
                    'name' => 'Format',
                    'parameter' => 'view',
                    'type' => 'radio',
                ],
            ],
            'parameters' => [
                'variables' => $variables,
            ],
        ];
    }
}
