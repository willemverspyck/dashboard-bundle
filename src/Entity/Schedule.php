<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Entity;

use App\Entity\Module;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;
use Stringable;

#[Doctrine\Table(name: 'schedule')]
#[Doctrine\Entity]
class Schedule implements Stringable
{
    #[Doctrine\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[Doctrine\Id]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[Doctrine\Column(name: 'name', type: Types::STRING, length: 256)]
    private string $name;

    #[Doctrine\Column(name: 'hours', type: Types::JSON)]
    private array $hours;

    #[Doctrine\Column(name: 'days', type: Types::JSON)]
    private array $days;

    #[Doctrine\Column(name: 'weeks', type: Types::JSON)]
    private array $weeks;

    #[Doctrine\Column(name: 'weekdays', type: Types::JSON)]
    private array $weekdays;

    /**
     * @var Collection<int, Module>
     */
    #[Doctrine\ManyToMany(targetEntity: Module::class)]
    #[Doctrine\JoinTable(name: 'schedule_module')]
    #[Doctrine\JoinColumn(name: 'schedule_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Doctrine\InverseJoinColumn(name: 'module_id', referencedColumnName: 'id')]
    private Collection $modules;

    public function __construct()
    {
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setHours(array $hours): static
    {
        $this->hours = $hours;

        return $this;
    }

    public function getHours(): array
    {
        return $this->hours;
    }

    public function setDays(array $days): static
    {
        $this->days = $days;

        return $this;
    }

    public function getDays(): array
    {
        return $this->days;
    }

    public function setWeeks(array $weeks): static
    {
        $this->weeks = $weeks;

        return $this;
    }

    public function getWeeks(): array
    {
        return $this->weeks;
    }

    public function setWeekdays(array $weekdays): static
    {
        $this->weekdays = $weekdays;

        return $this;
    }

    public function getWeekdays(): array
    {
        return $this->weekdays;
    }

    public function addModule(Module $module): static
    {
        $this->modules->add($module);

        return $this;
    }

    public function removeModule(Module $module): void
    {
        $this->modules->removeElement($module);
    }

    public function clearModules(): void
    {
        $this->modules->clear();
    }

    /**
     * @return Collection<int, Module>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}