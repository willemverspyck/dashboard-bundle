<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Message;

interface AbstractMessageInterface
{
    public function setId(int $id): void;

    public function getId(): int;
}
