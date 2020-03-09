<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DomPdfPrinter implements PdfPrinterInterface
{
    public function getStreamResponse(PdfInterface $pdf, ?PdfPrintOptions $options = null): StreamedResponse
    {
        $options = $options ?? new PdfPrintOptions([]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($pdf->getHtml());
        $dompdf->setPaper($options->getSize(), $options->getOrientation());
        $dompdf->setOptions(new Options(['isHtml5ParserEnabled' => $options->isHtml5Parsing()]));
        $dompdf->render();

        return new StreamedResponse(function () use ($dompdf, $pdf, $options) {
            $dompdf->stream($pdf->getFileName(), [
                'compress' => (int) $options->isCompress(),
                'Attachment' => (int) $options->isAttachment()
            ]);
        });
    }
}
