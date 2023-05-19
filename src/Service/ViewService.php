<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use Exception;
use Spyck\DashboardBundle\View\ViewInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class ViewService
{
    public function __construct(#[TaggedIterator(tag: 'spyck.dashboard.view', defaultIndexMethod: 'getName')] private readonly iterable $views)
    {
    }

    /**
     * @throws Exception
     */
    public function getView(string $name): ViewInterface
    {
        foreach ($this->views->getIterator() as $index => $view) {
            if ($index === $name) {
                return $view;
            }
        }

        throw new Exception(sprintf('No view available for %s', $name));
    }
}
