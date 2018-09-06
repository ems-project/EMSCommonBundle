<?php

namespace EMS\CommonBundle\Tests\Unit\Common;

use EMS\CommonBundle\Common\EMSLink;
use PHPUnit\Framework\TestCase;

class EMSLinkTest extends TestCase
{
    public function testFromText()
    {
        $link = EMSLink::fromText('ems://object:page:AWTLzKLc8K-kdP4iJ3rt');

        static::assertSame('AWTLzKLc8K-kdP4iJ3rt', $link->getOuuid());
        static::assertSame('page', $link->getContentType());
        static::assertSame('object', $link->getLinkType());
        static::assertSame('ems://object:page:AWTLzKLc8K-kdP4iJ3rt', (string) $link);
    }

    public function testFromTextOnlyOuuid()
    {
        $link = EMSLink::fromText('AWTLzKLc8K-kdP4iJ3rt');

        static::assertSame('AWTLzKLc8K-kdP4iJ3rt', $link->getOuuid());
        static::assertSame('object', $link->getLinkType());
        static::assertFalse($link->hasContentType());
        static::assertSame('ems://object:AWTLzKLc8K-kdP4iJ3rt', (string) $link);
    }

    public function testFromMatchNoOuuidShouldInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        EMSLink::fromMatch([]);
    }
}