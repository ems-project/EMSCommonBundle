<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfGenerator
{
    /** @var PdfPrinterInterface */
    private $pdfPrinter;

    public function __construct(PdfPrinterInterface $pdfPrinter)
    {
        $this->pdfPrinter = $pdfPrinter;
    }

    public function getResponse(string $html): StreamedResponse
    {
        $metaTags = $this->getMetaTags($html);
        $pdfPrintOptions = new PdfPrintOptions($this->sanitizeMetaTags($metaTags));
        $filename = $metaTags[PdfInterface::FILENAME] ?? 'export.pdf';

        return $this->pdfPrinter->getStreamResponse(new Pdf($filename, $html), $pdfPrintOptions);
    }

    private function getMetaTags(string $html): array
    {
        $metaTags = [];

        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $crawler->filterXPath('//meta[contains(@name, "pdf:")]')->each(
            function (Crawler $filterMetaTag) use (&$metaTags) {
                $name = substr($filterMetaTag->attr('name'), 4);
                $metaTags[$name] = $filterMetaTag->attr('content');
            }
        );

        return $metaTags;
    }

    private function sanitizeMetaTags(array $metaData): array
    {
        return filter_var_array($metaData, [
            PdfPrintOptions::ATTACHMENT => FILTER_VALIDATE_BOOLEAN,
            PdfPrintOptions::COMPRESS => FILTER_VALIDATE_BOOLEAN,
            PdfPrintOptions::HTML5_PARSING => FILTER_VALIDATE_BOOLEAN,
            PdfPrintOptions::ORIENTATION => null,
            PdfPrintOptions::SIZE => null,
        ], false);
    }
}