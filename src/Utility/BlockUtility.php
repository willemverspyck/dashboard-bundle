<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Utility;

use Spyck\DashboardBundle\Entity\Block;
use Symfony\Component\HttpFoundation\ParameterBag;

final class BlockUtility
{
    public static function getParameterBag(Block $block, array $variables): ParameterBag
    {
        $parameterBag = new ParameterBag();
        $parameterBag->add($block->getDashboard()->getVariables());
        $parameterBag->add($block->getVariables());
        $parameterBag->add($variables);

        return $parameterBag;
    }
}
