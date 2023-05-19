<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;
use Spyck\DashboardBundle\Repository\MailRepository;
use Spyck\DashboardBundle\View\ViewInterface;
use Stringable;

#[Doctrine\Entity(repositoryClass: MailRepository::class)]
#[Doctrine\Table(name: 'mail')]
class Mail implements Stringable
{
    #[Doctrine\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[Doctrine\Id]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[Doctrine\ManyToOne(targetEntity: Schedule::class)]
    #[Doctrine\JoinColumn(name: 'schedule_id', referencedColumnName: 'id', nullable: true)]
    private ?Schedule $schedule = null;

    #[Doctrine\ManyToOne(targetEntity: Dashboard::class)]
    #[Doctrine\JoinColumn(name: 'dashboard_id', referencedColumnName: 'id', nullable: false)]
    private Dashboard $dashboard;

    #[Doctrine\Column(name: 'name', type: Types::STRING, length: 256)]
    private string $name;

    #[Doctrine\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description;

    #[Doctrine\Column(name: 'code', type: Types::STRING, length: 128, nullable: true)]
    private ?string $code;

    #[Doctrine\Column(name: 'variables', type: Types::JSON)]
    private array $variables;

    #[Doctrine\Column(name: 'view', type: Types::STRING, length: 8)]
    private string $view;

    #[Doctrine\Column(name: 'route', type: Types::BOOLEAN)]
    private bool $route;

    #[Doctrine\Column(name: 'merge', type: Types::BOOLEAN)]
    private bool $merge;

    #[Doctrine\Column(name: 'active', type: Types::BOOLEAN)]
    private bool $active;

    /**
     * @var Collection<int, UserInterface>
     */
    #[Doctrine\ManyToMany(targetEntity: UserInterface::class)]
    #[Doctrine\JoinTable(name: 'mail_user')]
    #[Doctrine\JoinColumn(name: 'mail_id', referencedColumnName: 'id')]
    #[Doctrine\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();

        $this->setRoute(true);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setSchedule(?Schedule $schedule): static
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getSchedule(): ?Schedule
    {
        return $this->schedule;
    }

    public function setDashboard(Dashboard $dashboard): static
    {
        $this->dashboard = $dashboard;

        return $this;
    }

    public function getDashboard(): Dashboard
    {
        return $this->dashboard;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setVariables(array $variables): static
    {
        $this->variables = $variables;

        return $this;
    }

    public function getVariables(): array
    {
        return $this->variables;
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

    public function setRoute(bool $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function hasRoute(): bool
    {
        return $this->route;
    }

    public function setMerge(bool $merge): static
    {
        $this->merge = $merge;

        return $this;
    }

    public function isMerge(): bool
    {
        return $this->merge;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function addUser(UserInterface $user): static
    {
        $this->users->add($user);

        return $this;
    }

    public function removeUser(UserInterface $user): void
    {
        $this->users->removeElement($user);
    }

    public function clearUsers(): void
    {
        $this->users->clear();
    }

    /**
     * @return Collection<int, UserInterface>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public static function getMailViews(bool $inverse = false): array
    {
        $data = [
            ViewInterface::CSV => ViewInterface::CSV_NAME,
            ViewInterface::DOCX => ViewInterface::DOCX_NAME,
            ViewInterface::HTML => ViewInterface::HTML_NAME,
            ViewInterface::JSON => ViewInterface::JSON_NAME,
            ViewInterface::PDF => ViewInterface::PDF_NAME,
            ViewInterface::SSV => ViewInterface::SSV_NAME,
            ViewInterface::TSV => ViewInterface::TSV_NAME,
            ViewInterface::XLSX => ViewInterface::XLSX_NAME,
            ViewInterface::XML => ViewInterface::XML_NAME,
        ];

        if (false === $inverse) {
            return $data;
        }

        return array_flip($data);
    }

    public function __clone()
    {
        $this->id = null;

        $this->setName(sprintf('%s (Copy)', $this->getName()));
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
