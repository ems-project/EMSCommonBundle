<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

final class DomPdfPrinter implements PdfPrinterInterface
{
    public function print(Pdf $pdf, ?PdfPrintOptions $options = null)
    {
        $dompdf = $this->createDompdfFromPdf($pdf);

        $options = $options ?? new PdfPrintOptions([]);
        $dompdf->setOptions(new Options(['isHtml5ParserEnabled' => $options->isHtml5Parsing()]));

        $dompdf->render();
        $dompdf->stream($pdf->getFileName(), [
            'compress' => (int) $options->isCompress(),
            'Attachment' => (int) $options->isAttachment()
        ]);
    }

    private function createDompdfFromPdf(Pdf $pdf): Dompdf
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($pdf->getHtml());
        $dompdf->setPaper($pdf->getSize(), $pdf->getOrientation());

        return $dompdf;
    }
}
