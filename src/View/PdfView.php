<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\View;

use Spyck\DashboardBundle\Model\Dashboard;
use Spyck\DashboardBundle\Service\FileService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Twig\Environment;

final class PdfView extends AbstractView
{
    public function __construct(private readonly Environment $environment) # , private readonly FileService $fileService
    {
    }

    /**
     * @throws Exception
     */
    public function getContent(Dashboard $dashboard): string
    {
        $options = new Options();
        $options->setChroot([
            $this->fileService->getPublicDirectory(),
            $this->fileService->getPublicDirectory('cache'),
            $this->fileService->getTemporaryDirectoryForChart(),
        ]);

        $html = $this->environment->render('view/pdf.html.twig', [
            'dashboard' => $dashboard,
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $content = $dompdf->output();

        if (null === $content) {
            throw new Exception('Error creating PDF');
        }

        return $content;
    }

    public static function getContentType(): string
    {
        return 'application/pdf';
    }

    public static function getExtension(): string
    {
        return ViewInterface::PDF;
    }

    public static function getName(): string
    {
        return 'pdf';
    }
}
