<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Entity;

use App\Entity\Group;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;
use Stringable;

#[Doctrine\Table(name: 'privilege')]
#[Doctrine\Entity]
class Privilege implements Stringable
{
    #[Doctrine\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[Doctrine\Id]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[Doctrine\ManyToOne(targetEntity: Dashboard::class)]
    #[Doctrine\JoinColumn(name: 'dashboard_id', referencedColumnName: 'id', nullable: true)]
    private ?Dashboard $dashboard = null;

    #[Doctrine\Column(name: 'name', type: Types::STRING, length: 128)]
    private string $name;

    #[Doctrine\Column(name: 'roles', type: Types::JSON)]
    private array $roles;

    #[Doctrine\Column(name: 'priority', type: Types::SMALLINT, options: ['unsigned' => true])]
    private int $priority;

    /**
     * @var Collection<int, Group>
     */
    #[Doctrine\ManyToMany(targetEntity: Group::class, inversedBy: 'privileges')]
    #[Doctrine\JoinTable(name: 'privilege_group')]
    #[Doctrine\JoinColumn(name: 'privilege_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Doctrine\InverseJoinColumn(name: 'group_id', referencedColumnName: 'id')]
    private Collection $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDashboard(?Dashboard $dashboard): static
    {
        $this->dashboard = $dashboard;

        return $this;
    }

    public function getDashboard(): ?Dashboard
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

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function addGroup(Group $group): static
    {
        $this->groups->add($group);

        return $this;
    }

    public function removeGroup(Group $group): void
    {
        $this->groups->removeElement($group);
    }

    public function clearGroups(): void
    {
        $this->groups->clear();
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
