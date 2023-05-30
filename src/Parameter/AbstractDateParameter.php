<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Parameter;

use DateTime;
use DateTimeInterface;
use Exception;

abstract class AbstractDateParameter implements DateParameterInterface
{
    private ?DateTimeInterface $data = null;

    public function getData(): ?DateTimeInterface
    {
        return $this->data;
    }

    public function getDataAsString(bool $slug = false): ?string
    {
        $data = $this->getData();

        return $data?->format($slug ? 'Ymd' : 'Y-m-d');
    }

    public function getDataForQueryBuilder(): ?string
    {
        $data = $this->getData();

        return $data?->format('Y-m-d');
    }

    public function getDataForRequest(): ?string
    {
        $data = $this->getData();

        return $data?->format('Y-m-d');
    }

    public function getEnvironment(): ?string
    {
        return null;
    }

    /**
     * @throws Exception
     */
    public function setData(string $data): void
    {
        $this->data = new DateTime($data);
    }
}
