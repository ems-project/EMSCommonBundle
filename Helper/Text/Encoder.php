<?php

namespace EMS\CommonBundle\Helper\Text;

class Encoder
{
    public function htmlEncode(string $text): string
    {
        return mb_encode_numericentity(html_entity_decode($text), array(0x0, 0xffff, 0, 0xffff), 'UTF-8');
    }

    public function htmlEncodePii(string $text): string
    {
        return $this->encodePhone($this->encodeEmail($this->encodePiiClass($text)));
    }

    /**
     *
     * Detect telephone information using the '"tel:xxx"' pattern
     * <a href="tel:02/123.45.23">02/123.45.23</a>
     */
    private function encodePhone(string $text): string
    {
        $telRegex = '/(?P<tel>"tel:.*")/i';

        $encodedText = preg_replace_callback($telRegex, function ($match) {
            return $this->htmlEncode($match['tel']);
        }, $text);

        if ($encodedText === null) {
            return $text;
        }

        return $encodedText;
    }

    /**
     *
     * Detect url information using the '"http//host:proto/url/target"' pattern
     * <a href="http//host:proto/url/target">target</a>
     */
    public function encodeUrl(string $text): string
    {
        $urlRegex = '/(?P<proto>([\w\d\-\.]+:)?)\/\/(?P<host>[\w\d\-\.]+(:[0-9]+)?)\/(?P<baseurl>([\w\d\-\._]+\/)*)(?P<target>[\w\d\-\._]+)/';

        $encodedText = preg_replace_callback($urlRegex, function ($matches) {
            return sprintf('<a href="%s//%s/%s%s">%s</a>', $matches['proto'], $matches['host'], $matches['baseurl'], $matches['target'], $matches['target']);
        }, $text);

        if ($encodedText === null) {
            return $text;
        }

        return $encodedText;
    }

    /**
     *
     * Detect email information using the 'x@x.x' pattern
     * <a href="mailto:david.meert@smals.be">david.meert@smals.be</a>
     */
    private function encodeEmail(string $text): string
    {
        $emailRegex = '/(?P<email>[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3}))/i';

        $encodedText = preg_replace_callback($emailRegex, function ($match) {
            return $this->htmlEncode($match['email']);
        }, $text);

        if ($encodedText === null) {
            return $text;
        }

        return $encodedText;
    }

    /**
     *
     * Allow to encode other pii using a class "pii"
     * <a href="tel:02/123.45.23"><span class="pii">02/123.45.23</span></a>
     *
     * The <span> element is consumed and is not kept in the end result.
     * example browser output: <a href="tel:02/123.45.23">02/123.45.23</a>
     *
     * If html tags are used inside a pii span, it will be double encoded and give unexpected results on the browser
     */
    private function encodePiiClass(string $text): string
    {
        $piiRegex = '/<span class="pii">(?P<pii>.*)<\/span>/m';

        $encodedText = preg_replace_callback($piiRegex, function ($match) {
            return $this->htmlEncode($match['pii']);
        }, $text);

        if ($encodedText === null) {
            return $text;
        }

        return $encodedText;
    }
}
