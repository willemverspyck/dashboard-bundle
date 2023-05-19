<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;

#[Doctrine\HasLifecycleCallbacks]
trait TimestampableTrait
{
    #[Doctrine\Column(name: 'date_created', type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $dateCreated;

    #[Doctrine\Column(name: 'date_updated', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateUpdated = null;

    public function getDateCreated(): DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTimeInterface $dateCreated): static
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateUpdated(): ?DateTimeInterface
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(DateTimeInterface $dateUpdated): static
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    #[Doctrine\PrePersist]
    public function prePersist(): void
    {
        $date = new DateTime();

        $this->setDateCreated($date);
    }

    #[Doctrine\PreUpdate]
    public function preUpdate(): void
    {
        $date = new DateTime();

        $this->setDateUpdated($date);
    }
}
