<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Widget;

use Doctrine\ORM\QueryBuilder;

interface DoctrineInterface
{
    /**
     * Get data from Doctrine with QueryBuilder.
     */
    public function getDataFromDoctrine(): QueryBuilder;
}
