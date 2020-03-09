<?php

namespace EMS\CommonBundle\Service\Dom;

use Symfony\Component\DomCrawler\Crawler;

final class HtmlCrawler
{
    /** @var Crawler */
    private $crawler;

    public function __construct(string $html)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);

        $this->crawler = $crawler;
    }

    /**
     * @return \DOMElement[]
     */
    public function getMetaTagsByXpath(string $xPath):  \Generator
    {
        foreach ($this->crawler->filterXPath($xPath) as $metaTag) {
            yield $metaTag;
        }
    }
}