<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Pdf;

final class PdfPrintOptions
{
    private bool $attachment;
    private bool $compress;
    private bool $html5Parsing;
    private bool $isPhpEnabled;
    private string $orientation;
    private string $size;
    private ?string $chroot;

    public const ATTACHMENT = 'attachment';
    public const COMPRESS = 'compress';
    public const HTML5_PARSING = 'html5Parsing';
    public const PHP_ENABLED = 'phpEnabled';
    public const ORIENTATION = 'orientation';
    public const SIZE = 'size';
    public const CHROOT = 'chroot';

    /**
     * @param array<mixed> $options
     */
    public function __construct(array $options)
    {
        $this->attachment = $options[self::ATTACHMENT] ?? true;
        $this->compress = $options[self::COMPRESS] ?? true;
        $this->html5Parsing = $options[self::HTML5_PARSING] ?? true;
        $this->isPhpEnabled = $options[self::PHP_ENABLED] ?? false;
        $this->orientation = $options[self::ORIENTATION] ?? 'portrait';
        $this->size = $options[self::SIZE] ?? 'a4';
        $this->chroot = $options[self::CHROOT] ?? null;
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

    public function isPhpEnabled(): bool
    {
        return $this->isPhpEnabled;
    }

    public function getChroot(): ?string
    {
        return $this->chroot;
    }
}
