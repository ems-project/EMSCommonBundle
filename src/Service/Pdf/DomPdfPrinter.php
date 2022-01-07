<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DomPdfPrinter implements PdfPrinterInterface
{
    /** @var string[] */
    private array $domPdfRootDirectories;

    public function __construct(string $projectDir, string $cacheDir)
    {
        $this->domPdfRootDirectories = [$projectDir];
        if (0 !== \strpos($cacheDir, $projectDir)) {
            $this->domPdfRootDirectories[] = $cacheDir;
        }
    }

    public function getPdfOutput(PdfInterface $pdf, ?PdfPrintOptions $options = null): PdfOutput
    {
        $options = $options ?? new PdfPrintOptions([]);
        $dompdf = $this->makeDomPdf($pdf, $options);

        return new PdfOutput(function () use ($dompdf): ?string {
            return $dompdf->output();
        });
    }

    public function getStream(PdfInterface $pdf, ?PdfPrintOptions $options = null): callable
    {
        $options = $options ?? new PdfPrintOptions([]);
        $dompdf = $this->makeDomPdf($pdf, $options);

        return function () use ($dompdf, $pdf, $options) {
            $dompdf->stream($pdf->getFileName(), [
                'compress' => (int) $options->isCompress(),
                'Attachment' => (int) $options->isAttachment(),
            ]);
        };
    }

    public function getStreamedResponse(PdfInterface $pdf, ?PdfPrintOptions $options = null): StreamedResponse
    {
        return new StreamedResponse($this->getStream($pdf, $options));
    }

    private function makeDomPdf(PdfInterface $pdf, PdfPrintOptions $options): Dompdf
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($pdf->getHtml());
        $dompdf->setPaper($options->getSize(), $options->getOrientation());
        $dompdf->setOptions(new Options([
            'isHtml5ParserEnabled' => $options->isHtml5Parsing(),
            'isPhpEnabled' => $options->isPhpEnabled(),
            'chroot' => \array_filter(\array_merge($this->domPdfRootDirectories, [$options->getChroot()])),
        ]));
        $dompdf->render();

        return $dompdf;
    }
}
