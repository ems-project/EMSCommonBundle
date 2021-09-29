<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

use EMS\CommonBundle\Service\Dom\HtmlCrawler;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfGenerator
{
    /** @var PdfPrinterInterface */
    private $pdfPrinter;

    public function __construct(PdfPrinterInterface $pdfPrinter)
    {
        $this->pdfPrinter = $pdfPrinter;
    }

    public function getStreamedResponse(string $html): StreamedResponse
    {
        $metaTags = $this->getMetaTags($html);
        $pdfPrintOptions = new PdfPrintOptions($this->sanitizeMetaTags($metaTags));
        $filename = $metaTags[PdfInterface::FILENAME] ?? 'export.pdf';

        return $this->pdfPrinter->getStreamedResponse(new Pdf($filename, $html), $pdfPrintOptions);
    }

    /**
     * @return array<string, string>
     */
    private function getMetaTags(string $html): array
    {
        $metaTags = [];
        $crawler = new HtmlCrawler($html);

        foreach ($crawler->getMetaTagsByXpath('//meta[contains(@name, "pdf:")]') as $node) {
            $name = \substr($node->getAttribute('name'), 4);
            $metaTags[$name] = $node->getAttribute('content');
        }

        return $metaTags;
    }

    /**
     * @param array<mixed> $metaData
     *
     * @return array<mixed>
     */
    private function sanitizeMetaTags(array $metaData): array
    {
        $filtered = \filter_var_array($metaData, [
            PdfPrintOptions::ATTACHMENT => FILTER_VALIDATE_BOOLEAN,
            PdfPrintOptions::COMPRESS => FILTER_VALIDATE_BOOLEAN,
            PdfPrintOptions::HTML5_PARSING => FILTER_VALIDATE_BOOLEAN,
            PdfPrintOptions::PHP_ENABLED => FILTER_VALIDATE_BOOLEAN,
            PdfPrintOptions::ORIENTATION => null,
            PdfPrintOptions::SIZE => null,
        ], false);

        if (!\is_array($filtered)) {
            throw new \RuntimeException('Unexpected sanitizeMetaTags error');
        }

        return $filtered;
    }
}
