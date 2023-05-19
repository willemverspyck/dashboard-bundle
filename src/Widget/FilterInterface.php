<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

interface FilterInterface extends RequestInterface
{
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_INPUT = 'input';

    public function getData(): ?array;

    public function getType(): string;

    public function setData(array $data): void;

    public function setType(string $type): void;
}