<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\View;

use Spyck\DashboardBundle\Entity\Widget;
use Spyck\DashboardBundle\Model\Block;
use Spyck\DashboardBundle\Model\Dashboard;
use Spyck\DashboardBundle\Model\Field;
use DateTimeInterface;
use Exception;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord as Document;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\SimpleType\Zoom;
use PhpOffice\PhpWord\Style\Table;
use Spyck\DashboardBundle\Service\ChartService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class WordView extends AbstractView
{
    private Document $document;

    public function __construct(private readonly ChartService $chartService, #[Autowire('%spyck.dashboard.directory%')] private readonly string $directory)
    {
    }

    /**
     * @throws Exception
     */
    public function getContent(Dashboard $dashboard): string
    {
        ob_start();

        $this->setDocument($dashboard);
        $this->addHeader($dashboard);
        $this->addFooter();

        $dashboard->getBlocks()->forAll(function (int $index, Block $block): bool {
            $this->addPage($index, $block);

            return true;
        });

        $wordWriter = IOFactory::createWriter($this->document);
        $wordWriter->save('php://output');

        return ob_get_clean();
    }

    public static function getContentType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    public static function getExtension(): string
    {
        return ViewInterface::DOCX;
    }

    public static function getName(): string
    {
        return ViewInterface::DOCX;
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
            Field::TYPE_BOOLEAN => $value ? '✓' : '✕',
            Field::TYPE_CURRENCY => sprintf('€ %s', $this->getValueOfNumber($typeOptions, $value)),
            Field::TYPE_IMAGE => sprintf('%s%s', $this->directory, $value),
            Field::TYPE_NUMBER => $this->getValueOfNumber($typeOptions, $value),
            Field::TYPE_PERCENTAGE => sprintf('%s%%', $this->getValueOfNumber($typeOptions, $value * 100)),
            Field::TYPE_POSITION => sprintf('%s/images/icons/position_%s.png', $this->directory, 0.0 === $value ? 'equal' : ($value > 0 ? 'greater' : 'less')),
            default => parent::getValue($type, $typeOptions, $value),
        };
    }

    private function setDocument(Dashboard $dashboard): void
    {
        Settings::setOutputEscapingEnabled(true);

        $this->document = new Document();

        $docInfo = $this->document->getDocInfo();
        $docInfo->setTitle($dashboard->getName());

        if (null !== $dashboard->getDescription()) {
            $docInfo->setDescription($dashboard->getDescription());
        }

        $docInfo->setCreator($dashboard->getUser());

        $copyright = $dashboard->getCopyright();

        if (null !== $copyright) {
            $docInfo->setCompany($copyright);
        }

        $this->document->setDefaultFontName('Arial');
        $this->document->setDefaultFontSize(10);

        $this->document->getSettings()->setZoom(Zoom::TEXT_FIT);

        $this->document->addTitleStyle(1, $this->getStyleTitle());
    }

    /**
     * @throws Exception
     */
    private function addHeader(Dashboard $dashboard): void
    {
        $section = $this->document->addSection($this->getStyleSection());

        $header = $section->addHeader();

        $tableRow = $header
            ->addTable($this->getStyleTable($section, false))
            ->addRow();

        $cell = $tableRow->addCell(2500);

        foreach ($dashboard->getParametersAsString() as $parameter) {
            $cell->addText(sprintf('%s', $parameter), $this->getStyleFontForHeader());
        }

        $cell = $tableRow->addCell(2200);
        $cell->addText($dashboard->getName(), $this->getStyleFontForHeader(), [
            'alignment' => Jc::END,
        ]);

        $cell = $tableRow->addCell(300);
        $cell->addImage(sprintf('%s/images/icon.png', $this->directory), $this->getStyleImageForHeader());
    }

    private function addFooter(): void
    {
        $section = $this->document->addSection($this->getStyleSection());

        $footer = $section->addFooter();

        $tableRow = $footer
            ->addTable($this->getStyleTable($section, false))
            ->addRow();

        $cell = $tableRow->addCell(5000);
        $cell->addPreserveText('Page {PAGE} of {NUMPAGES}', $this->getStyleFontForFooter(), [
            'alignment' => Jc::END,
        ]);
    }

    /**
     * @throws Exception
     */
    private function addPage(int $index, Block $block): void
    {
        $section = $this->document->addSection($this->getStyleSection());

        if ($index > 0) {
            $section->addTextBreak(2);
        }

        $section->addTitle($block->getName());
        $section->addTextBreak();

        if (null !== $block->getDescription()) {
            $section->addText($block->getDescription(), $this->getStyleText());
            $section->addTextBreak();
        }

        $widget = $block->getWidget();

        $filters = $widget->getFilters();

        if ($block->hasFilterView() && count($filters) > 0) {
            $section->addText('Active filters', $this->getStyleFilterTitle());

            foreach ($filters as $filter) {
                $section->addText(sprintf('%s: %s', ucfirst($filter['name']), implode(', ', $filter['data'])), $this->getStyleFilterText());
            }

            $section->addTextBreak();
        }

        $data = $widget->getData();

        if (0 === count($data)) {
            $section->addText($block->getDescriptionEmpty(), $this->getStyleText());

            return;
        }

        if (Widget::CHART_TABLE === $block->getChart()) {
            $table = $section->addTable($this->getStyleTable($section));
            $tableRow = $table->addRow();

            $fields = $widget->getFields();

            foreach ($fields as $field) {
                $tableRow->addCell(null, $this->getStyleTableForHeader())->addText($field['name'], $this->getStyleFontForHeaderOfTable());
            }

            foreach ($data as $index => $row) {
                $tableRow = $table->addRow();

                foreach ($row['fields'] as $fieldIndex => $field) {
                    $tableCell = $tableRow->addCell(null, $this->getStyleTableForBody(0 !== $index % 2));

                    $value = $this->getValue($fields[$fieldIndex]['type'], $fields[$fieldIndex]['typeOptions'], $field['value']);

                    if (null !== $value) {
                        if (Field::TYPE_IMAGE === $fields[$fieldIndex]['type']) {
                            $tableCell->addImage($value, $this->getStyleImageForBodyOfTable());
                        } elseif (Field::TYPE_POSITION === $fields[$fieldIndex]['type']) {
                            $tableCell->addImage($value, $this->getStylePositionForBodyOfTable());
                        } else {
                            $color = $this->getColor($fields[$fieldIndex]['type'], $fields[$fieldIndex]['typeOptions'], $field['value']);

                            $tableCell->addText($value, $this->getStyleFontForBodyOfTable($color));
                        }
                    }
                }
            }

            return;
        }

        $chart = $this->chartService->getChart($block);

        $source = file_get_contents($chart);

        if (false !== $source) {
            $section->addImage($source, $this->getStyleChart($section));
        }
    }

    /**
     * Get the column conditional styles.
     */
    private function getColor(string $type, array $typeOptions, array|bool|DateTimeInterface|float|int|string|null $value): ?string
    {
        if (false === in_array($type, [Field::TYPE_CURRENCY, Field::TYPE_NUMBER, Field::TYPE_PERCENTAGE, Field::TYPE_POSITION], true)) {
            return null;
        }

        if (null === $typeOptions['condition']) {
            return null;
        }

        foreach ($typeOptions['condition'] as $condition) {
            if (null !== $condition['start'] && null !== $condition['end']) {
                if ($value >= $condition['start'] && $value <= $condition['end']) {
                    return $condition['color'];
                }
            } elseif (null !== $condition['start']) {
                if ($value >= $condition['start']) {
                    return $condition['color'];
                }
            } elseif (null !== $condition['end']) {
                if ($value <= $condition['end']) {
                    return $condition['color'];
                }
            }
        }

        return null;
    }

    private function getStyleSection(): array
    {
        return [
            'breakType' => 'continuous',
            'marginTop' => Converter::pixelToTwip(40),
            'marginRight' => Converter::pixelToTwip(40),
            'marginBottom' => Converter::pixelToTwip(40),
            'marginLeft' => Converter::pixelToTwip(40),
        ];
    }

    private function getStyleTitle(): array
    {
        return [
            'name' => 'Futura',
            'color' => '53595e',
            'size' => 20,
            'bold' => true,
        ];
    }

    private function getStyleText(): array
    {
        return [
            'color' => '808080',
        ];
    }

    private function getStyleFilterTitle(): array
    {
        return [
            'name' => 'Futura',
            'color' => '53595e',
            'size' => 14,
            'bold' => true,
        ];
    }

    private function getStyleFilterText(): array
    {
        return [
            'color' => '808080',
        ];
    }

    private function getStyleTable(Section $section, bool $spacing = true): array
    {
        $style = $section->getStyle();

        return [
            'unit' => TblWidth::TWIP,
            'width' => $style->getPageSizeW() - $style->getMarginLeft() - $style->getMarginRight(),
            'layout' => Table::LAYOUT_FIXED,
            'borderSize' => 'none',
            'cellMarginTop' => Converter::pixelToTwip(8),
            'cellMarginRight' => Converter::pixelToTwip($spacing ? 10 : 2),
            'cellMarginBottom' => Converter::pixelToTwip(8),
            'cellMarginLeft' => Converter::pixelToTwip($spacing ? 10 : 2),
            'cantSplit' => true,
        ];
    }

    private function getStyleTableForHeader(): array
    {
        return [
            'bgColor' => '6bd398',
        ];
    }

    private function getStyleTableForBody(bool $alternate = false): array
    {
        return [
            'bgColor' => $alternate ? 'f3f3f3' : 'ffffff',
        ];
    }

    private function getStyleFontForHeader(): array
    {
        return [
            'name' => 'Futura',
            'color' => '53595e',
            'size' => 10,
            'bold' => true,
        ];
    }

    private function getStyleFontForFooter(): array
    {
        return $this->getStyleFontForHeader();
    }

    private function getStyleFontForHeaderOfTable(): array
    {
        return [
            'color' => '53595e',
            'allCaps' => true,
            'bold' => true,
        ];
    }

    private function getStyleFontForBodyOfTable(string $color = null): array
    {
        return [
            'color' => null === $color ? '808080' : $color,
        ];
    }

    private function getStyleImageForHeader(): array
    {
        return [
            'alignment' => Jc::END,
            'width' => Converter::pixelToPoint(30),
            'height' => Converter::pixelToPoint(30),
        ];
    }

    private function getStyleImageForBodyOfTable(): array
    {
        return [
            'width' => Converter::pixelToPoint(40),
            'height' => Converter::pixelToPoint(40),
        ];
    }

    private function getStylePositionForBodyOfTable(): array
    {
        return [
            'width' => Converter::pixelToPoint(16),
            'height' => Converter::pixelToPoint(16),
        ];
    }

    private function getStyleChart(Section $section): array
    {
        $style = $section->getStyle();

        return [
            'width' => $this->twipToPoint($style->getPageSizeW() - $style->getMarginLeft() - $style->getMarginRight()),
        ];
    }

    private function twipToPoint(float $twip): float
    {
        return $twip * (Converter::INCH_TO_POINT / Converter::INCH_TO_TWIP);
    }
}
