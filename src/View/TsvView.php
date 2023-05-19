<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\View;

final class TsvView extends CsvView
{
    /**
     * {@inheritDoc}
     */
    public static function getContentType(): string
    {
        return 'text/tab-separated-values';
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtension(): string
    {
        return ViewInterface::TSV;
    }

    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return ViewInterface::TSV;
    }

    protected function getSeparator(): string
    {
        return chr(9);
    }
}
