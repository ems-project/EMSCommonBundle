<?php

namespace EMS\CommonBundle\Helper\Text;

use cebe\markdown\GithubMarkdown;

class Encoder
{
    public function htmlEncode(string $text): string
    {
        return \mb_encode_numericentity(\html_entity_decode($text), [0x0, 0xffff, 0, 0xffff], 'UTF-8');
    }

    public function htmlEncodePii(string $text): string
    {
        return $this->encodePhone($this->encodeEmail($this->encodePiiClass($text)));
    }

    /**
     * Detect telephone information using the '"tel:xxx"' pattern
     * <a href="tel:02/123.45.23">02/123.45.23</a>.
     */
    private function encodePhone(string $text): string
    {
        $telRegex = '/(?P<tel>"tel:.*")/i';

        $encodedText = \preg_replace_callback($telRegex, function ($match) {
            return $this->htmlEncode($match['tel']);
        }, $text);

        if (null === $encodedText) {
            return $text;
        }

        return $encodedText;
    }

    /**
     * Detect url information using the '"http//host:proto/url/target"' pattern
     * <a href="http//host:proto/url/target">target</a>.
     */
    public function encodeUrl(string $text): string
    {
        $urlRegex = '/(?P<proto>([\w\d\-\.]+:)?)\/\/(?P<host>[\w\d\-\.]+(:[0-9]+)?)\/(?P<baseurl>([\w\d\-\._]+\/)*)(?P<target>[\w\d\-\._]+)/';

        $encodedText = \preg_replace_callback($urlRegex, function ($matches) {
            return \sprintf('<a href="%s//%s/%s%s">%s</a>', $matches['proto'], $matches['host'], $matches['baseurl'], $matches['target'], $matches['target']);
        }, $text);

        if (null === $encodedText) {
            return $text;
        }

        return $encodedText;
    }

    public static function webalize(string $text): ?string
    {
        $a = ['Ã€', 'Ã�', 'Ã‚', 'Ãƒ', 'Ã„', 'Ã…', 'Ã†', 'Ã‡', 'Ãˆ', 'Ã‰', 'ÃŠ', 'Ã‹', 'ÃŒ', 'Ã�', 'ÃŽ', 'Ã�', 'Ã�', 'Ã‘', 'Ã’', 'Ã“', 'Ã”', 'Ã•', 'Ã–', 'Ã˜', 'Ã™', 'Ãš', 'Ã›', 'Ãœ', 'Ã�', 'ÃŸ', 'Ã ', 'Ã¡', 'Ã¢', 'Ã£', 'Ã¤', 'Ã¥', 'Ã¦', 'Ã§', 'Ã¨', 'Ã©', 'Ãª', 'Ã«', 'Ã¬', 'Ã­', 'Ã®', 'Ã¯', 'Ã±', 'Ã²', 'Ã³', 'Ã´', 'Ãµ', 'Ã¶', 'Ã¸', 'Ã¹', 'Ãº', 'Ã»', 'Ã¼', 'Ã½', 'Ã¿', 'Ä€', 'Ä�', 'Ä‚', 'Äƒ', 'Ä„', 'Ä…', 'Ä†', 'Ä‡', 'Äˆ', 'Ä‰', 'ÄŠ', 'Ä‹', 'ÄŒ', 'Ä�', 'ÄŽ', 'Ä�', 'Ä�', 'Ä‘', 'Ä’', 'Ä“', 'Ä”', 'Ä•', 'Ä–', 'Ä—', 'Ä˜', 'Ä™', 'Äš', 'Ä›', 'Äœ', 'Ä�', 'Äž', 'ÄŸ', 'Ä ', 'Ä¡', 'Ä¢', 'Ä£', 'Ä¤', 'Ä¥', 'Ä¦', 'Ä§', 'Ä¨', 'Ä©', 'Äª', 'Ä«', 'Ä¬', 'Ä­', 'Ä®', 'Ä¯', 'Ä°', 'Ä±', 'Ä²', 'Ä³', 'Ä´', 'Äµ', 'Ä¶', 'Ä·', 'Ä¹', 'Äº', 'Ä»', 'Ä¼', 'Ä½', 'Ä¾', 'Ä¿', 'Å€', 'Å�', 'Å‚', 'Åƒ', 'Å„', 'Å…', 'Å†', 'Å‡', 'Åˆ', 'Å‰', 'ÅŒ', 'Å�', 'ÅŽ', 'Å�', 'Å�', 'Å‘', 'Å’', 'Å“', 'Å”', 'Å•', 'Å–', 'Å—', 'Å˜', 'Å™', 'Åš', 'Å›', 'Åœ', 'Å�', 'Åž', 'ÅŸ', 'Å ', 'Å¡', 'Å¢', 'Å£', 'Å¤', 'Å¥', 'Å¦', 'Å§', 'Å¨', 'Å©', 'Åª', 'Å«', 'Å¬', 'Å­', 'Å®', 'Å¯', 'Å°', 'Å±', 'Å²', 'Å³', 'Å´', 'Åµ', 'Å¶', 'Å·', 'Å¸', 'Å¹', 'Åº', 'Å»', 'Å¼', 'Å½', 'Å¾', 'Å¿', 'Æ’', 'Æ ', 'Æ¡', 'Æ¯', 'Æ°', 'Ç�', 'ÇŽ', 'Ç�', 'Ç�', 'Ç‘', 'Ç’', 'Ç“', 'Ç”', 'Ç•', 'Ç–', 'Ç—', 'Ç˜', 'Ç™', 'Çš', 'Ç›', 'Çœ', 'Çº', 'Ç»', 'Ç¼', 'Ç½', 'Ç¾', 'Ç¿', '\''];
        $b = ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', ' '];
        $clean = \str_replace($a, $b, $text);

        $clean = \preg_replace("/[^a-zA-Z0-9\_\|\ \-\.]/", '', $clean) ?? '';
        $clean = \strtolower(\trim($clean, '-'));
        $clean = \preg_replace("/[\/\_\|\ \-]+/", '-', $clean);

        return $clean;
    }

    public static function markdownToHtml(string $markdown): string
    {
        static $parser;
        if (null === $parser) {
            $parser = new GithubMarkdown();
        }

        return $parser->parse($markdown);
    }

    /**
     * Detect email information using the 'x@x.x' pattern
     * <a href="mailto:david.meert@smals.be">david.meert@smals.be</a>.
     */
    private function encodeEmail(string $text): string
    {
        $emailRegex = '/(?P<email>[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3}))/i';

        $encodedText = \preg_replace_callback($emailRegex, function ($match) {
            return $this->htmlEncode($match['email']);
        }, $text);

        if (null === $encodedText) {
            return $text;
        }

        return $encodedText;
    }

    /**
     * Allow to encode other pii using a class "pii"
     * <a href="tel:02/123.45.23"><span class="pii">02/123.45.23</span></a>.
     *
     * The <span> element is consumed and is not kept in the end result.
     * example browser output: <a href="tel:02/123.45.23">02/123.45.23</a>
     *
     * If html tags are used inside a pii span, it will be double encoded and give unexpected results on the browser
     */
    private function encodePiiClass(string $text): string
    {
        $piiRegex = '/<span class="pii">(?P<pii>.*)<\/span>/m';

        $encodedText = \preg_replace_callback($piiRegex, function ($match) {
            return $this->htmlEncode($match['pii']);
        }, $text);

        if (null === $encodedText) {
            return $text;
        }

        return $encodedText;
    }

    /**
     * @return string
     */
    public static function getFontAwesomeFromMimeType(string $mimeType)
    {
        // List of official MIME Types: http://www.iana.org/assignments/media-types/media-types.xhtml
        $icon_classes = [
            // Media
            'image' => 'fa-file-image-o',
            'audio' => 'fa-file-audio-o',
            'video' => 'fa-file-video-o',
            // Documents
            'application/pdf' => 'fa-file-pdf-o',
            'application/msword' => 'fa-file-word-o',
            'application/vnd.ms-word' => 'fa-file-word-o',
            'application/vnd.oasis.opendocument.text' => 'fa-file-word-o',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'fa-file-word-o',
            'application/vnd.ms-excel' => 'fa-file-excel-o',
            'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'fa-file-excel-o',
            'application/vnd.oasis.opendocument.spreadsheet' => 'fa-file-excel-o',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint-o',
            'application/vnd.openxmlformats-officedocument.presentationml' => 'fa-file-powerpoint-o',
            'application/vnd.oasis.opendocument.presentation' => 'fa-file-powerpoint-o',
            'text/plain' => 'fa-file-text-o',
            'text/html' => 'fa-file-code-o',
            'application/json' => 'fa-file-code-o',
            // Archives
            'application/gzip' => 'fa-file-archive-o',
            'application/zip' => 'fa-file-archive-o',
            'application/x-zip' => 'fa-file-archive-o',
        ];

        foreach ($icon_classes as $text => $icon) {
            if (0 === \strpos($mimeType, $text)) {
                return $icon;
            }
        }

        return 'fa-file-o';
    }
}
