<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Entity;

use Spyck\DashboardBundle\Repository\WidgetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;
use Stringable;
use Symfony\Component\Validator\Constraints as Validator;

#[Doctrine\Entity(repositoryClass: WidgetRepository::class)]
#[Doctrine\Table(name: 'widget')]
class Widget implements Stringable
{
    public const CHART_AREA = 'area';
    public const CHART_AREA_NAME = 'Area';
    public const CHART_COLUMN = 'column';
    public const CHART_COLUMN_NAME = 'Column';
    public const CHART_COUNTRY = 'country';
    public const CHART_COUNTRY_NAME = 'Country';
    public const CHART_GANTT = 'gantt';
    public const CHART_GANTT_NAME = 'Gantt';
    public const CHART_LINE = 'line';
    public const CHART_LINE_NAME = 'Line';
    public const CHART_PIE = 'pie';
    public const CHART_PIE_NAME = 'Pie';
    public const CHART_REGION = 'region';
    public const CHART_REGION_NAME = 'Region';
    public const CHART_TABLE = 'table';
    public const CHART_TABLE_NAME = 'Table';

    public const PERMISSION_LABEL = 'ROLE_LABEL';
    public const PERMISSION_LABEL_NAME = 'Label';
    public const PERMISSION_COMPANY = 'ROLE_COMPANY';
    public const PERMISSION_COMPANY_NAME = 'Company';

    #[Doctrine\Column(name: 'id', type: Types::SMALLINT, options: ['unsigned' => true])]
    #[Doctrine\Id]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[Doctrine\ManyToOne(targetEntity: Privilege::class)]
    #[Doctrine\JoinColumn(name: 'privilege_id', referencedColumnName: 'id', nullable: false)]
    private Privilege $privilege;

    #[Doctrine\Column(name: 'name', type: Types::STRING, length: 128)]
    #[Validator\NotNull(message: 'This value is required')]
    private string $name;

    #[Doctrine\Column(name: 'description', type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[Doctrine\Column(name: 'description_empty', type: Types::TEXT, nullable: true)]
    #[Validator\NotNull(message: 'This value is required')]
    protected ?string $descriptionEmpty = null;

    #[Doctrine\Column(name: 'adapter', type: Types::STRING, length: 128)]
    #[Validator\NotNull(message: 'This value is required')]
    private string $adapter;

    #[Doctrine\Column(name: 'parameters', type: Types::JSON, nullable: false)]
    private array $parameters;

    #[Doctrine\Column(name: 'permission', type: Types::JSON)]
    private array $permission;

    #[Doctrine\Column(name: 'charts', type: Types::JSON)]
    private array $charts;

    #[Doctrine\Column(name: 'chart', type: Types::TEXT)]
    private string $chart;

    #[Doctrine\Column(name: 'active', type: Types::BOOLEAN)]
    private bool $active;

    use TimestampableTrait;

    /**
     * @var Collection<int, Block>
     */
    #[Doctrine\OneToMany(mappedBy: 'widget', targetEntity: Block::class)]
    private Collection $blocks;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();

        $this->setParameters([]);
        $this->setPermission([]);
        $this->setCharts([]);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setPrivilege(Privilege $privilege): static
    {
        $this->privilege = $privilege;

        return $this;
    }

    public function getPrivilege(): Privilege
    {
        return $this->privilege;
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

    public function getDescriptionEmpty(): ?string
    {
        return $this->descriptionEmpty;
    }

    public function setDescriptionEmpty(?string $descriptionEmpty): static
    {
        $this->descriptionEmpty = $descriptionEmpty;

        return $this;
    }

    public function setAdapter(string $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setPermission(array $permission): static
    {
        $this->permission = $permission;

        return $this;
    }

    public function getPermission(): array
    {
        return $this->permission;
    }

    public function setCharts(array $charts): static
    {
        $this->charts = $charts;

        return $this;
    }

    public function getCharts(): array
    {
        return $this->charts;
    }

    public function setChart(string $chart): static
    {
        $this->chart = $chart;

        return $this;
    }

    public function getChart(): string
    {
        return $this->chart;
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

    public function addBlock(Block $block): static
    {
        $this->blocks->add($block);

        return $this;
    }

    public function removeBlock(Block $block): void
    {
        $this->blocks->removeElement($block);
    }

    public function clearBlocks(): void
    {
        $this->blocks->clear();
    }

    /**
     * @return Collection<int, Block>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public static function getWidgetCharts(bool $inverse = false): array
    {
        $data = [
            self::CHART_AREA => self::CHART_AREA_NAME,
            self::CHART_COLUMN => self::CHART_COLUMN_NAME,
            self::CHART_COUNTRY => self::CHART_COUNTRY_NAME,
            self::CHART_GANTT => self::CHART_GANTT_NAME,
            self::CHART_LINE => self::CHART_LINE_NAME,
            self::CHART_PIE => self::CHART_PIE_NAME,
            self::CHART_REGION => self::CHART_REGION_NAME,
            self::CHART_TABLE => self::CHART_TABLE_NAME,
        ];

        if (false === $inverse) {
            return $data;
        }

        return array_flip($data);
    }

    public static function getWidgetPermission(bool $inverse = false): array
    {
        $data = [
            self::PERMISSION_COMPANY => self::PERMISSION_COMPANY_NAME,
            self::PERMISSION_LABEL => self::PERMISSION_LABEL_NAME,
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
