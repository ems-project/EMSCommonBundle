<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

interface PdfPrinterInterface
{
    public function print(Pdf $pdf, ?PdfPrintOptions $options = null);
}
