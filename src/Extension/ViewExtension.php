<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Extension;

use Doctrine\Common\Collections\Collection;
use Exception;
use Spyck\DashboardBundle\Model\Block;
use Spyck\DashboardBundle\Service\AmChartService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

#[Autoconfigure(tags: ['twig.extension'])]
final class ViewExtension extends AbstractExtension
{
    public function __construct(private readonly AmChartService $amChartService, #[Autowire('%spyck.dashboard.chart.directory%')] private readonly string $directory)
    {
    }

    /**
     * Set the functions for this extension.
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getAbbreviation', [$this, 'getAbbreviation']),
            new TwigFunction('getChart', [$this, 'getChart']),
            new TwigFunction('getDirectory', [$this, 'getDirectory']),
        ];
    }

    /**
     * @throws Exception
     */
    public function getAbbreviation(float|int $value, int $precision = 0): string
    {
        return NumberUtility::getAbbreviation($value, $precision);
    }

    /**
     * @throws Exception
     */
    public function getChart(Block $block): string
    {
        $tableView = new TableView();

        $data = $tableView->getWidget($block->getWidget());

        return $this->amChartService->getChart($data, $block->getChart());
    }

    /**
     * @throws Exception
     */
    public function getDirectory(string $value): string
    {
        return sprintf('%s%s', $this->directory, $value);
    }
}
