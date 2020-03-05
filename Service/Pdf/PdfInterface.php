<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

interface PdfInterface
{
    public function getFileName(): string;
    public function getHtml(): string;
    public function getOrientation(): string;
    public function getSize(): string;
}
