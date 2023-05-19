<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Controller;

use Exception;
use OpenApi\Attributes as OpenApi;
use Spyck\DashboardBundle\Model\Block;
use Spyck\DashboardBundle\Service\ViewService;
use Spyck\DashboardBundle\Service\WidgetService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[AsController]
#[OpenApi\Tag(name: 'Widgets')]
final class WidgetController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/api/widget/{widgetId}.{_format}', name: 'spyck_dashboard_widget_show', requirements: ['widgetId' => Requirement::DIGITS], methods: [Request::METHOD_GET])]
    public function show(Request $request, ViewService $viewService, WidgetService $widgetService, int $widgetId): Response
    {
        set_time_limit(120);

        $variables = $request->query->all();

        $widget = $widgetService->getWidgetDataById($widgetId, $variables);

        $viewInstance = $viewService->getView($request->getRequestFormat());

        $content = $viewInstance->getContent($widget);

        $headers = [];

        if (null !== $viewInstance->getExtension()) {
            $headers['Content-Disposition'] = sprintf('attachment; filename="%s.%s"', $viewInstance->getFile($widget->getName(), $widget->getParametersAsStringForSlug()), $viewInstance->getExtension());
        }

        return new Response($content, Response::HTTP_OK, $headers);
    }

    /**
     * @throws Exception
     */
    #[Cache(expires: '+2 hour', maxage: '7200', smaxage: '7200', public: true)]
    #[Route(path: '/api/v1/widget/{widgetId}', name: 'spyck_dashboard_widget_item', requirements: ['widgetId' => Requirement::DIGITS], methods: [Request::METHOD_GET])]
    public function item(Request $request, WidgetService $widgetService, int $widgetId): Response
    {
        $variables = $request->query->all();

        $block = $widgetService->getWidgetDataById($widgetId, $variables)->getBlocks()->first();

        $widget = null;

        if ($block instanceof Block) {
            $widget = $block->getWidget();
        }

        return $this->getItemResponse($widget, ['widget']);
    }
}
