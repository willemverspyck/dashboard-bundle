<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\View;

use Spyck\DashboardBundle\Model\Block;
use Spyck\DashboardBundle\Model\Dashboard;
use Spyck\DashboardBundle\Model\Field;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;

final class ExcelView extends AbstractView
{
    private Spreadsheet $spreadsheet;

    public function __construct(private readonly CacheItemPoolInterface $cacheItemPool)
    {
    }

    public function getContent(Dashboard $dashboard): string
    {
        $this->setSpreadsheet($dashboard);

        $dashboard->getBlocks()->forAll(function (int $index, Block $block): bool {
            $this->addSheet($index, $block);

            return true;
        });

        $this->spreadsheet->setActiveSheetIndex(0);

        ob_start();

        $excelWriterObject = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $excelWriterObject->save('php://output');

        return ob_get_clean();
    }

    public static function getContentType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public static function getExtension(): string
    {
        return ViewInterface::XLSX;
    }

    public static function getName(): string
    {
        return ViewInterface::XLSX;
    }

    /**
     * {@inheritDoc}
     */
    protected function getValue(string $type, array $typeOptions, array|bool|DateTimeInterface|float|int|string|null $value): bool|float|int|string|null
    {
        if (null === $value) {
            return null;
        }

        return match ($type) {
            Field::TYPE_ARRAY => implode(PHP_EOL, $value),
            Field::TYPE_DATE, Field::TYPE_DATETIME, Field::TYPE_TIME => Date::dateTimeToExcel($value),
            default => $value,
        };
    }

    private function setSpreadsheet(Dashboard $dashboard): void
    {
        $psr16Cache = new Psr16Cache($this->cacheItemPool);

        Settings::setCache($psr16Cache);

        $this->spreadsheet = new Spreadsheet();

        $properties = $this->spreadsheet->getProperties();
        $properties->setTitle($dashboard->getName());

        if (null !== $dashboard->getDescription()) {
            $properties->setDescription($dashboard->getDescription());
        }

        $properties->setCreator($dashboard->getUser());
        $properties->setCompany($dashboard->getCopyright());
    }

    private function addSheet(int $index, Block $block): void
    {
        if ($index > 0) {
            $sheet = $this->spreadsheet->createSheet();
        } else {
            $sheet = $this->spreadsheet->getActiveSheet();
        }

        $sheet->setTitle(substr($block->getName(), 0, 24));

        $widget = $block->getWidget();

        $fields = $widget->getFields();

        foreach ($fields as $fieldIndex => $field) {
            $sheet->setCellValueExplicit([$fieldIndex + 1, 1], $field['name'], DataType::TYPE_STRING);

            $style = $sheet->getStyle([$fieldIndex + 1, 2, $fieldIndex + 1, count($widget->getData()) + 1]);

            $columnFormat = $this->getColumnFormat($field['type'], $field['typeOptions']);

            if (null !== $columnFormat) {
                $style
                    ->getNumberFormat()
                    ->setFormatCode($columnFormat);
            }

            $columnConditional = $this->getColumnCondition($field['type'], $field['typeOptions']);

            if (null !== $columnConditional) {
                $style
                    ->setConditionalStyles($columnConditional);
            }
        }

        foreach ($widget->getData() as $rowIndex => $row) {
            foreach ($row['fields'] as $fieldIndex => $field) {
                $value = $this->getValue($fields[$fieldIndex]['type'], $fields[$fieldIndex]['typeOptions'], $field['value']);

                $columnType = $this->getColumnType($fields[$fieldIndex]['type'], $value);

                $sheet->setCellValueExplicit([$fieldIndex + 1, $rowIndex + 2], $value, $columnType);
            }
        }

        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        $sheet->setSelectedCells([1, 1, 1, 1]);
    }

    /**
     * Get the column format.
     */
    private function getColumnFormat(string $type, array $typeOptions): ?string
    {
        return match ($type) {
            Field::TYPE_CURRENCY => sprintf('[$€ ] #,##0%s', null === $typeOptions['precision'] ? '' : sprintf('.%s_-', str_repeat('0', $typeOptions['precision']))),
            Field::TYPE_DATE => NumberFormat::FORMAT_DATE_DDMMYYYY,
            Field::TYPE_DATETIME => sprintf('%s hh:mm:ss', NumberFormat::FORMAT_DATE_DDMMYYYY),
            Field::TYPE_NUMBER => sprintf('#,##0%s', null === $typeOptions['precision'] ? '' : sprintf('.%s_-', str_repeat('0', $typeOptions['precision']))),
            Field::TYPE_PERCENTAGE => sprintf('0%s%%', null === $typeOptions['precision'] ? '' : sprintf('.%s', str_repeat('0', $typeOptions['precision']))),
            Field::TYPE_TIME => 'hh:mm:ss',
            default => null,
        };
    }

    /**
     * Get the column conditional styles.
     */
    private function getColumnCondition(string $type, array $typeOptions): ?array
    {
        $content = null;

        switch ($type) {
            case Field::TYPE_CURRENCY:
            case Field::TYPE_PERCENTAGE:
            case Field::TYPE_NUMBER:
                if (null !== $typeOptions['condition']) {
                    $content = [];

                    foreach ($typeOptions['condition'] as $condition) {
                        $conditional = new Conditional();
                        $conditional->setConditionType(Conditional::CONDITION_CELLIS);

                        if (null !== $condition['start'] && null !== $condition['end']) {
                            $conditional->setOperatorType(Conditional::OPERATOR_BETWEEN);
                            $conditional->addCondition($condition['start']);
                            $conditional->addCondition($condition['end']);
                        } elseif (null !== $condition['start']) {
                            $conditional->setOperatorType(Conditional::OPERATOR_GREATERTHANOREQUAL);
                            $conditional->addCondition($condition['start']);
                        } elseif (null !== $condition['end']) {
                            $conditional->setOperatorType(Conditional::OPERATOR_LESSTHAN);
                            $conditional->addCondition($condition['end']);
                        } else {
                            $conditional->setOperatorType(Conditional::OPERATOR_NONE);
                        }

                        $conditional->getStyle()->getFont()->getColor()->setRGB($condition['color']);

                        $content[] = $conditional;
                    }
                }

                break;
        }

        return $content;
    }

    /**
     * Get the column format.
     */
    private function getColumnType(string $type, bool|float|int|string|null $value): string
    {
        if (null === $value) {
            return DataType::TYPE_NULL;
        }

        return match ($type) {
            Field::TYPE_ARRAY, Field::TYPE_IMAGE, Field::TYPE_TEXT => DataType::TYPE_STRING,
            Field::TYPE_BOOLEAN => DataType::TYPE_BOOL,
            default => DataType::TYPE_NUMERIC,
        };
    }
}
