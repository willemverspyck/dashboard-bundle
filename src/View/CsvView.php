<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\View;

use Spyck\DashboardBundle\Model\Block;
use Spyck\DashboardBundle\Model\Dashboard;
use Exception;
use SplFileObject;

class CsvView extends AbstractView
{
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

        ob_start();

        $splFileObject = new SplFileObject('php://output', 'w');

        $fields = $widget->getFields();

        $data = [];

        foreach ($fields as $field) {
            $data[] = $field['name'];
        }

        $splFileObject->fputcsv($data, $this->getSeparator());

        foreach ($widget->getData() as $row) {
            $data = [];

            foreach ($row['fields'] as $fieldIndex => $field) {
                $data[] = $this->getValue($fields[$fieldIndex]['type'], $fields[$fieldIndex]['typeOptions'], $field['value']);
            }

            $splFileObject->fputcsv($data, $this->getSeparator());
        }

        return ob_get_clean();
    }

    public static function getContentType(): string
    {
        return 'text/csv';
    }

    public static function getExtension(): string
    {
        return ViewInterface::CSV;
    }

    public static function getName(): string
    {
        return ViewInterface::CSV;
    }

    /**
     * {@inheritDoc}
     */
    public static function isMerge(): ?bool
    {
        return false;
    }

    protected function getSeparator(): string
    {
        return ',';
    }
}
