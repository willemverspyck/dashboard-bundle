<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\MessageHandler;

use Spyck\DashboardBundle\Entity\Activity;
use Spyck\DashboardBundle\Entity\UserInterface;
use Spyck\DashboardBundle\Message\MailMessageInterface;
use Spyck\DashboardBundle\Entity\Dashboard;
use Spyck\DashboardBundle\Model\Dashboard as DashboardAsModel;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Spyck\DashboardBundle\Repository\ActivityRepository;
use Spyck\DashboardBundle\Repository\DashboardRepository;
use Spyck\DashboardBundle\Repository\UserRepository;
use Spyck\DashboardBundle\Service\DashboardService;
use Spyck\DashboardBundle\Service\MailService;
use Spyck\DashboardBundle\Service\ViewService;
use Spyck\DashboardBundle\Utility\FileUtility;
use Spyck\DashboardBundle\View\ViewInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AsMessageHandler]
final class MailMessageHandler
{
    public function __construct(private readonly ActivityRepository $activityRepository, private readonly DashboardRepository $dashboardRepository, private readonly DashboardService $dashboardService, private readonly MailService $mailService, private readonly TokenStorageInterface $tokenStorage, private readonly UserRepository $userRepository, private readonly ViewService $viewService)
    {
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function __invoke(MailMessageInterface $mailMessage): void
    {
        $user = $this->getUserById($mailMessage->getUser());

        $token = $this->tokenStorage->getToken();

        $usernamePasswordToken = new UsernamePasswordToken($user, get_class($this), $user->getRoles());

        $this->tokenStorage->setToken($usernamePasswordToken);

        $dashboard = $this->getDashboardById($mailMessage->getId());

        $this->sendMailWithMessage($mailMessage, $user, $dashboard);

        $this->tokenStorage->setToken($token);

        $this->activityRepository->putActivity($user, $dashboard, $mailMessage->getVariables(), $mailMessage->getView(), Activity::TYPE_MAIL);
    }

    /**
     * @throws NonUniqueResultException
     * @throws UnrecoverableMessageHandlingException
     */
    private function getUserById(int $id): UserInterface
    {
        $user = $this->userRepository->getUserById($id);

        if (null === $user) {
            throw new UnrecoverableMessageHandlingException(sprintf('User not found (%d)', $id));
        }

        return $user;
    }

    /**
     * Check if the dashboard exists and if user has access.
     *
     * @throws AuthenticationException
     * @throws NonUniqueResultException
     */
    private function getDashboardById(int $id): Dashboard
    {
        $dashboard = $this->dashboardRepository->getDashboardById($id);

        if (null === $dashboard) {
            throw new AuthenticationException(sprintf('Dashboard not found (%d) (Access Denied)', $id));
        }

        return $dashboard;
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    private function sendMailWithMessage(MailMessageInterface $mailMessage, UserInterface $user, Dashboard $dashboard): void
    {
        $dashboardAsModel = $this->dashboardService->getDashboardAsModel($dashboard, $mailMessage->getVariables(), $mailMessage->getView(), true);

        $data = [
            'dashboard' => $dashboardAsModel,
            'mail' => $mailMessage,
        ];

        $attachments = [];

        if (ViewInterface::HTML !== $mailMessage->getView()) {
            $view = $this->viewService->getView($mailMessage->getView());

            if (null === $view->isMerge()) {
                $merge = $mailMessage->isMerge();
            } else {
                $merge = $view->isMerge();
            }

            if ($merge) {
                $attachments[] = $this->getAttachment($dashboardAsModel, $view);
            } else {
                foreach ($dashboardAsModel->getBlocks() as $block) {
                    $dashboardAsModel->clearBlocks();

                    $dashboardAsModel->addBlock($block);

                    $attachments[] = $this->getAttachment($dashboardAsModel, $view, $block->getName());
                }
            }
        }

        $subject = [
            $mailMessage->getName(),
        ];

        foreach ($dashboardAsModel->getParametersAsString() as $name => $parameter) {
            if (false === in_array($name, ['DayRangeParameter', 'MonthRangeParameter', 'WeekRangeParameter'], true)) {
                $subject[] = sprintf('%s', $parameter);
            }
        }

        $this->mailService->sendMail($user->getEmail(), $user->getName(), implode(' | ', $subject), '@SpyckDashboard/mail/index.html.twig', $data, $attachments);
    }

    private function getAttachment(DashboardAsModel $dashboard, ViewInterface $view, string $name = null): DataPart
    {
        $content = $view->getContent($dashboard);

        $filename = $this->getFilename(null === $name ? $dashboard->getName() : $name, $dashboard->getParametersAsStringForSlug(), $view->getExtension());

        return new DataPart($content, $filename, $view->getContentType());
    }

    private function getFilename(string $name, array $parameters, string $extension): string
    {
        $filename = [
            $name,
        ];

        foreach ($parameters as $parameter) {
            $filename[] = sprintf('%s', $parameter);
        }

        $slug = FileUtility::filter(implode('-', $filename));

        return sprintf('%s.%s', $slug, $extension);
    }
}
