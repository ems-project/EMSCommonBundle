<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

final class Pdf implements PdfInterface
{
    /** @var string */
    private $fileName;
    /** @var string */
    private $html;
    /** @var string */
    private $size = 'A4';
    /** @var string */
    private $orientation = 'portrait';

    public function __construct(string $fileName, string $html)
    {
        $this->fileName = $fileName;
        $this->html = $html;
    }

    public function changeSize(string $size): void
    {
        $this->size = $size;
    }

    public function changeOrientation(string $orientation): void
    {
        $this->orientation = $orientation;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getOrientation(): string
    {
        return $this->orientation;
    }
}
