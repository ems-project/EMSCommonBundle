<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

final class PdfPrintOptions
{
    /** @var bool */
    private $attachment;
    /** @var bool */
    private $compress;
    /** @var bool */
    private $html5Parsing;
    /** @var string */
    private $orientation;
    /** @var string */
    private $size;
    private ?string $chroot;

    public const ATTACHMENT = 'attachment';
    public const COMPRESS = 'compress';
    public const HTML5_PARSING = 'html5Parsing';
    public const ORIENTATION = 'orientation';
    public const SIZE = 'size';
    public const CHROOT = 'chroot';

    public function __construct(array $options)
    {
        $this->setAttachment($options[self::ATTACHMENT] ?? true);
        $this->setCompress($options[self::COMPRESS] ?? true);
        $this->setHtml5Parsing($options[self::HTML5_PARSING] ?? true);
        $this->setOrientation($options[self::ORIENTATION] ?? 'portrait');
        $this->setSize($options[self::SIZE] ?? 'a4');
        $this->setChroot($options[self::CHROOT] ?? null);
    }

    public function getOrientation(): string
    {
        return $this->orientation;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function isAttachment(): bool
    {
        return $this->attachment;
    }

    public function isCompress(): bool
    {
        return $this->compress;
    }

    public function isHtml5Parsing(): bool
    {
        return $this->html5Parsing;
    }

    private function setAttachment(bool $attachment): void
    {
        $this->attachment = $attachment;
    }

    private function setCompress(bool $compress): void
    {
        $this->compress = $compress;
    }

    private function setHtml5Parsing(bool $html5Parsing): void
    {
        $this->html5Parsing = $html5Parsing;
    }

    private function setOrientation(string $orientation): void
    {
        $this->orientation = $orientation;
    }

    private function setSize(string $size): void
    {
        $this->size = $size;
    }

    public function getChroot(): ?string
    {
        return $this->chroot;
    }

    public function setChroot(?string $chroot): void
    {
        $this->chroot = $chroot;
    }
}
