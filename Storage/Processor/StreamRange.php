<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Processor;

use Symfony\Component\HttpFoundation\HeaderBag;

class StreamRange
{
    /** @var int */
    private $fileSize;
    /** @var int */
    private $end;
    /** @var int */
    private $start;

    public function __construct(HeaderBag $headerBag, int $fileSize)
    {
        $this->fileSize = $fileSize;
        $this->start = 0;
        $this->end = $this->fileSize - 1;

        $this->parseRangeHeader($headerBag);

        if ($this->start > $this->end) {
            throw new \RuntimeException('Out of range exception');
        }
    }

    private function parseRangeHeader(HeaderBag $headerBag)
    {
        $range = $headerBag->get('Range');
        if ($range === null) {
            return;
        }

        list($start, $end) = explode('-', substr($range, 6), 2) + [0];

        $this->end = ('' === $end) ? $this->fileSize - 1 : (int) $end;

        if ('' === $start) {
            $this->start = $this->fileSize - $this->end;
            $this->end = $this->fileSize - 1;
        } else {
            $this->start = (int) $start;
        }
    }

    public function isSatisfiable()
    {
        return $this->start >= 0 && $this->end < $this->fileSize;
    }

    public function isPartial()
    {
        return $this->isSatisfiable() && ($this->start > 0 || $this->end < ($this->fileSize - 1));
    }

    public function getContentRangeHeader()
    {
        if ($this->isSatisfiable()) {
            return sprintf('bytes %s-%s/%s', $this->start, $this->end, $this->fileSize);
        }
        return sprintf('bytes */%s', $this->fileSize);
    }

    public function getContentLengthHeader()
    {
        return strval($this->end - $this->start + 1);
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }
}
