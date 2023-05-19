<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Controller;

use Spyck\DashboardBundle\Entity\UserInterface;
use Spyck\DashboardBundle\Form\DashboardMailType;
use Spyck\DashboardBundle\Message\MailMessage;
use Spyck\DashboardBundle\Model\Dashboard;
use Spyck\DashboardBundle\Repository\DashboardRepository;
use Spyck\DashboardBundle\Schema;
use Spyck\DashboardBundle\Service\ActivityService;
use Spyck\DashboardBundle\Service\DashboardService;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use OpenApi\Attributes as OpenApi;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsController]
#[OpenApi\Tag(name: 'Dashboard')]
final class DefaultController extends AbstractController
{
    #[Route(path: '/', name: 'spyck_dashboard_default_index', methods: [Request::METHOD_GET])]
    public function index(AuthorizationCheckerInterface $authorizationChecker, DashboardService $dashboardService): Response
    {
        $dashboardRoute = $dashboardService->getDashboardRouteOfPrivilege();

        if (null !== $dashboardRoute) {
            return $this->redirect($dashboardRoute->getUrl());
        }

        if ($authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        throw $this->createNotFoundException('No rights');
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/dashboard/{dashboardId}', name: 'spyck_dashboard_default_show', requirements: ['dashboardId' => Requirement::DIGITS], methods: [Request::METHOD_GET])]
    public function show(DashboardRepository $dashboardRepository, Request $request, int $dashboardId): Response
    {
        $dashboard = $dashboardRepository->getDashboardById($dashboardId);

        assert($dashboard instanceof Dashboard, $this->createNotFoundException('The dashboard does not exist'));

        $parameters = $request->query->all();
        $parameters['dashboardId'] = $dashboard->getId();

        return $this->render('dashboard/index.html.twig', [
            'dashboard' => $this->generateUrl('app_dashboard_item', $parameters),
            'menu' => $this->generateUrl('app_menu_list'),
            'user' => $this->generateUrl('app_user_item'),
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/api/v1/dashboard/{dashboardId}', name: 'spyck_dashboard_default_item', requirements: ['dashboardId' => Requirement::DIGITS], methods: [Request::METHOD_GET])]
    #[Schema\BadRequest]
    #[Schema\Forbidden]
    #[Schema\NotFound]
    #[Schema\ResponseItem(type: Dashboard::class, groups: ['dashboard'])]
    public function item(ActivityService $activityService, DashboardRepository $dashboardRepository, DashboardService $dashboardService, Request $request, int $dashboardId): Response
    {
        $dashboard = $dashboardRepository->getDashboardById($dashboardId);

        if (null === $dashboard) {
            return $this->getItemResponse();
        }

        $variables = $request->query->all();

        $requests = $dashboardService->checkDashboardParameterData($dashboard, $variables);

        if (null === $requests) {
            $activityService->putActivity($dashboard);

            return $this->getItemResponse($dashboardService->getDashboardAsModel($dashboard, $variables), ['dashboard']);
        }

        $data = [
            'error' => true,
            'name' => $dashboard->getName(),
            'requests' => $requests,
        ];

        return new JsonResponse($data);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/api/v1/dashboard/{dashboardId}/mail', name: 'spyck_dashboard_default_mail', requirements: ['dashboardId' => Requirement::DIGITS], methods: [Request::METHOD_POST])]
    public function mail(DashboardRepository $dashboardRepository, DashboardService $dashboardService, MessageBusInterface $messageBus, Request $request, TokenStorageInterface $tokenStorage, int $dashboardId): Response
    {
        $dashboard = $dashboardRepository->getDashboardById($dashboardId);

        if (null === $dashboard) {
            return $this->getItemResponse();
        }

        $data = json_decode($request->getContent(), true);

        if (false === array_key_exists('variables', $data)) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $requests = $dashboardService->checkDashboardParameterData($dashboard, $data['variables']);

        if (null !== $requests) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        return $this->getForm(DashboardMailType::class, new MailMessage(), $data, function (MailMessage $mailMessage) use ($dashboard, $tokenStorage, $messageBus, $data): array {
            /** @var UserInterface $user */
            $user = $tokenStorage->getToken()->getUser();

            $mailMessage->setId($dashboard->getId());
            $mailMessage->setUser($user->getId());
            $mailMessage->setVariables($data['variables']);

            $messageBus->dispatch($mailMessage);

            return [];
        });
    }
}
