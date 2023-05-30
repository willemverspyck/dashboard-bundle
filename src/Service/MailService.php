<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use Spyck\DashboardBundle\Entity\Mail;
use Spyck\DashboardBundle\Entity\Schedule;
use Spyck\DashboardBundle\Message\MailMessage;
use Spyck\DashboardBundle\Repository\MailRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Security\Core\User\UserInterface;

class MailService
{
    public function __construct(private readonly BodyRendererInterface $bodyRenderer, private readonly MailRepository $mailRepository, private readonly MessageBusInterface $messageBus, private readonly MailerInterface $mailer, #[Autowire('%spyck.dashboard.mailer.from.email%')] private readonly string $fromEmail, #[Autowire('%spyck.dashboard.mailer.from.name%')] private readonly string $fromName)
    {
    }

    public function handleMailMessageByMail(Mail $mail, array $parameters = []): void
    {
        foreach ($mail->getUsers() as $user) {
            $this->handleMailMessage($mail, $user, $parameters);
        }
    }

    public function handleMailMessageBySchedule(Schedule $schedule, array $parameters = []): void
    {
        $mails = $this->mailRepository->getMailDataBySchedule($schedule);

        foreach ($mails as $mail) {
            $this->handleMailMessageByMail($mail, $parameters);
        }
    }

    public function handleMailMessage(Mail $mail, UserInterface $user, array $parameters = []): void
    {
        $dashboard = $mail->getDashboard();

        $mailMessage = new MailMessage();
        $mailMessage->setId($dashboard->getId());
        $mailMessage->setUser($user->getId());
        $mailMessage->setName($mail->getName());
        $mailMessage->setDescription($mail->getDescription());
        $mailMessage->setVariables(array_merge($mail->getVariables(), $parameters));
        $mailMessage->setView($mail->getView());
        $mailMessage->setRoute($mail->hasRoute());
        $mailMessage->setMerge($mail->isMerge());

        $this->messageBus->dispatch($mailMessage);
    }
    
    /**
     * Send and render e-mail.
     *
     * @throws TransportExceptionInterface
     */
    public function sendMail(string $toEmail, ?string $toName, string $subject, string $template, array $data = [], array $attachments = []): void
    {
        $email = new TemplatedEmail();
        
        $from = new Address($this->fromEmail, $this->fromName);
        $to = new Address($toEmail, null === $toName ? '' : $toName);

        $email
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($data);

        foreach ($attachments as $attachment) {
            if ($attachment instanceof DataPart) {
                $email->addPart($attachment);
            }
        }

        $this->bodyRenderer->render($email);

        $this->mailer->send($email);
    }
}
