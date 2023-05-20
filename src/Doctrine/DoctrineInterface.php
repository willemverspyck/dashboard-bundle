<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;

interface DoctrineInterface
{
    /**
     * Get data from Doctrine with QueryBuilder.
     */
    public function getDataFromDoctrine(): QueryBuilder;
}
