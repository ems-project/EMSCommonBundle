<?php

namespace EMS\CommonBundle\Common;

class EMSLink
{
    /**
     * object|asset
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
     * content_type and query can be empty/optional
     *
     * Regex101.com:
     * ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>.*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)(?:\?(?P<query>(?:[^"|\'|\s]*)))?
     *
     * Example: <a href="ems://object:page:AV44kX4b1tfmVMOaE61u">example</a>
     * link_type => object, content_type => page, ouuid => AV44kX4b1tfmVMOaE61u
     */
    const REGEX = '/ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>.*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)(?:\?(?P<query>(?:[^"|\'|\s]*)))?/';

    /**
     * @param array $match
     *
     * @return EMSLink
     */
    public static function fromMatch(array $match): EMSLink
    {
        $link = new self();
        $link->linkType = $match['link_type'];
        $link->ouuid = $match['ouuid'];

        if (isset($match['content_type'])) {
            $link->contentType = $match['content_type'];
        }

        if (isset($match['query'])) {
            $link->query = html_entity_decode($match['query']);
        }


        return $link;
    }

    /**
     * @param string $string
     *
     * @return EMSLink
     */
    public static function fromString(string $string): EMSLink
    {
        $split = preg_split('/:/', $string);

        $link = new self();
        $link->contentType = $split[0];
        $link->ouuid = $split[1];

        return $link;
    }

    /**
     * @param array $document
     *
     * @return EMSLink
     */
    public static function fromDocument(array $document): EMSLink
    {
        $link = new self();
        $link->contentType = $document['_source']['_contenttype'];
        $link->ouuid = $document['_id'];

        return $link;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return vsprintf('ems://%s:%s%s%s', [
            $this->linkType,
            ($this->contentType ? $this->contentType . ':' : ''),
            $this->ouuid,
            ($this->query ? '?'. $this->query : '')
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

    /**
     * @return string
     */
    public function getOuuid(): string
    {
        return $this->ouuid;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        parse_str($this->query, $output);

        return $output;
    }

    /**
     * @return bool
     */
    public function hasContentType(): bool
    {
        return null !== $this->contentType;
    }
}