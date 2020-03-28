<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Helper\Header;

use Symfony\Component\HttpFoundation\Request;

class Range
{
    /** @var int */
    private $start;

    /** @var int */
    private $end;

    /** @var int */
    private $length;

    /** @var bool */
    private $satisfiable;

    /** @var bool */
    private $provided;

    const HTTP_RANGE = 'HTTP_RANGE';
    const HTTP_RANGE_REGEX = '/bytes=\h*(?P<rangeStart>\d+)-(?P<rangeEnd>\d*)[\D.*]?/i';

    function __construct(Request $request, ?int $fileSize)
    {
        $this->start = 0;
        $this->provided = false;
        $this->satisfiable = false;

        if ($fileSize === null) {
            return;
        }

        $this->end = $fileSize - 1;
        $this->length = $fileSize;

        $httpRange = $request->server->get(self::HTTP_RANGE);

        if ($httpRange === null) {
            return;
        }

        $this->provided = true;

        if (!preg_match(self::HTTP_RANGE_REGEX, $httpRange, $matches)) {
            return;
        }

        $rangeStart = intval($matches['rangeStart']);
        if (!empty($matches['rangeEnd'])) {
            $rangeEnd  = intval($matches['rangeEnd']);
        } else {
            $rangeEnd = $this->end;
        }

        if ($rangeStart > $rangeEnd) {
            return;
        }

        $this->start = $rangeStart;
        $this->end = $rangeEnd;
        $this->length = $rangeEnd - $rangeStart + 1;
        $this->satisfiable = true;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function isSatisfiable(): bool
    {
        return $this->satisfiable;
    }

    public function isProvided(): bool
    {
        return $this->provided;
    }
}
