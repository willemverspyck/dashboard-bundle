<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Parameter;

interface EntityParameterInterface extends ParameterInterface
{
    public function getData(): ?int;

    public function getDataAsObject(): ?object;

    public function getRoute(): ?string;

    /**
     * Check if the parameters is an interaction from the user.
     */
    public function isRequest(): bool;

    public function setDataAsObject(?object $dataAsObject): void;

    public function setRequest(bool $request): void;
}
