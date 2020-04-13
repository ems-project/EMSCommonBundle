<?php

namespace EMS\CommonBundle\Storage\Processor;

use Symfony\Component\HttpFoundation\HeaderBag;

class StreamRange
{
    /** @var bool */
    private $supported;
    /** @var int */
    private $fileSize;
    /** @var int */
    private $end;
    /** @var int */
    private $start;

    public function __construct(HeaderBag $headerBag, int $fileSize)
    {
        $this->fileSize = $fileSize;
        $this->supported = false;

        $range = $headerBag->get('Range');
        if ($range === null) {
            return;
        }

        $this->supported = true;
        list($start, $end) = explode('-', substr($range, 6), 2) + [0];

        $this->end = ('' === $end) ? $fileSize - 1 : (int) $end;

        if ('' === $start) {
            $this->start = $this->fileSize - $this->end;
            $this->end = $this->fileSize - 1;
        } else {
            $this->start = (int) $start;
        }
    }

    public function isOutOfRange()
    {
        return !$this->supported || $this->start > $this->end;
    }

    public function isSatisfiable()
    {
        return $this->supported && $this->start >= 0 && $this->end < $this->fileSize;
    }

    public function rangeRequested()
    {
        return $this->supported && (0 !== $this->start || $this->end !== $this->fileSize - 1);
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
