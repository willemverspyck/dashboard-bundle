<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\View;

use Spyck\DashboardBundle\Model\Block;
use Spyck\DashboardBundle\Model\Dashboard;
use Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractSerializerView extends AbstractView
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    /**
     * @throws Exception
     */
    public function getContent(Dashboard $dashboard): string
    {
        $block = $dashboard->getBlocks()->first();

        if (false === $block instanceof Block) {
            throw new Exception('No "Block" model');
        }

        $widget = $block->getWidget();

        return $this->serializer->serialize($widget, $this->getName(), [
            AbstractNormalizer::GROUPS => [
                'widget',
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public static function isMerge(): ?bool
    {
        return false;
    }
}
