<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common;

final class EMSLink
{
    /**
     * object|asset.
     *
     * @var string
     */
    private $linkType = 'object';

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var string
     */
    private $ouuid;

    /**
     * @var string
     */
    private $query = null;

    /**
     * Regex for searching ems links in content
     * content_type and query can be empty/optional.
     *
     * Example: <a href="ems://object:page:AV44kX4b1tfmVMOaE61u">example</a>
     * link_type => object, content_type => page, ouuid => AV44kX4b1tfmVMOaE61u
     */
    const PATTERN = '/ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>([[:alnum:]]|_)*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)(?:\?(?P<query>(?:[^"|\'|\s]*)))?/';
    const SIMPLE_PATTERN = '/(?:(?P<content_type>.*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)/';

    private function __construct()
    {
    }

    public static function fromText(string $text): EMSLink
    {
        $pattern = 'ems://' === \substr($text, 0, 6) ? self::PATTERN : self::SIMPLE_PATTERN;
        \preg_match($pattern, $text, $match);

        return self::fromMatch($match);
    }

    public static function fromMatch(array $match): EMSLink
    {
        $link = new self();

        if (!isset($match['ouuid'])) {
            throw new \InvalidArgumentException(\sprintf('ouuid is required! (%s)', \implode(',', $match)));
        }

        $link->ouuid = $match['ouuid'];
        $link->linkType = $match['link_type'] ?? 'object';

        if (!empty($match['content_type'])) {
            $link->contentType = $match['content_type'];
        } elseif (!empty($match['link_type'])) {
            $link->contentType = $match['link_type'];
        }

        if (!empty($match['query'])) {
            $link->query = \html_entity_decode($match['query']);
        }

        return $link;
    }

    public static function fromDocument(array $document): EMSLink
    {
        $source = $document['_source'];

        $link = new self();

        $link->contentType = isset($source['_contenttype']) ? $source['_contenttype'] : $document['_type'];
        $link->ouuid = $document['_id'];

        return $link;
    }

    public function __toString(): string
    {
        return \vsprintf('ems://%s:%s%s%s', [
            $this->linkType,
            ($this->contentType ? $this->contentType.':' : ''),
            $this->ouuid,
            ($this->query ? '?'.$this->query : ''),
        ]);
    }

    /**
     * @return string
     */
    public function getLinkType()
    {
        return $this->linkType;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    public function getOuuid(): string
    {
        return $this->ouuid;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        \parse_str($this->query, $output);

        return $output;
    }

    public function hasContentType(): bool
    {
        return null !== $this->contentType;
    }
}
