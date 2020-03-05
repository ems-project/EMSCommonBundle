<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

class PdfPrintOptions
{
    /** @var bool */
    private $compress;
    /** @var bool */
    private $attachment;
    /** @var bool */
    private $html5Parsing;

    public const HTML5_PARSING = 'html5Parsing';
    public const COMPRESS = 'compress';
    public const ATTACHMENT = 'attachment';

    public function __construct(array $options)
    {
        $this->setAttachment($options[self::ATTACHMENT] ?? true);
        $this->setCompress($options[self::COMPRESS] ?? true);
        $this->setHtml5Parsing($options[self::HTML5_PARSING] ?? true);
    }

    public function isCompress(): bool
    {
        return $this->compress;
    }

    public function setCompress(bool $compress): void
    {
        $this->compress = $compress;
    }

    public function isAttachment(): bool
    {
        return $this->attachment;
    }

    public function setAttachment(bool $attachment): void
    {
        $this->attachment = $attachment;
    }

    public function isHtml5Parsing(): bool
    {
        return $this->html5Parsing;
    }

    public function setHtml5Parsing(bool $html5Parsing): void
    {
        $this->html5Parsing = $html5Parsing;
    }
}
