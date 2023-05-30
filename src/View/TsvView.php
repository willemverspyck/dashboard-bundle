<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\View;

final class TsvView extends CsvView
{
    public static function getContentType(): string
    {
        return 'text/tab-separated-values';
    }

    public static function getExtension(): string
    {
        return ViewInterface::TSV;
    }

    public static function getName(): string
    {
        return ViewInterface::TSV;
    }

    protected function getSeparator(): string
    {
        return chr(9);
    }
}
