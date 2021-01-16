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
        $a = ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', '\''];
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
}
