<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Controller;

use OpenApi\Attributes as OpenApi;
use Spyck\DashboardBundle\Entity\Menu;
use Spyck\DashboardBundle\Repository\MenuRepository;
use Spyck\DashboardBundle\Schema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[OpenApi\Tag(name: 'Menu')]
final class MenuController extends AbstractController
{
    /**
     * Display the menu page.
     */
    #[Route(path: '/api/v1/menus', name: 'spyck_dashboard_menu_list', methods: [Request::METHOD_GET])]
    #[Schema\BadRequest]
    #[Schema\Forbidden]
    #[Schema\NotFound]
    #[Schema\ResponseItem(type: Menu::class, groups: ['menu'])]
    public function list(MenuRepository $menuRepository): Response
    {
        $menuData = $menuRepository
            ->getMenuData()
            ->getQuery()
            ->getResult();

        return $this->getListResponse($menuData, ['menu']);
    }
}
