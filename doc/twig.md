# Twig filters

## ems_anti_spam

For obfuscation of pii on your website when the user agent is a robot.

Implementation details are based on http://www.wbwip.com/wbw/emailencoder.html using `ems_html_encode`.
The following data can be obfuscated (even inside a wysiwyg field):

- emailadress `no_reply@example.com`
````twig
{{- 'no_reply@example.com'|ems_anti_spam -}}
````
- phone number in `<a href="tel:____">`
````twig
{{- '<a href="tel:02/123.50.00">repeated here, the number will not be encoded</a>'|ems_anti_spam -}}
````
- custom selection of pii using a span with class "pii"
````twig
{{- '<span class="pii">02/123.50.00</span>'|ems_anti_spam -}}
````

See unit test for more examples.

Note: Phone numbers are only obfuscated if they are found inside "tel:" notation. When a phone is used
outside an anchor, the custom selection of pii method should be used.

Note: When using custom selection of pii, make sure that no HTML tags are present inside the pii span.

Note: the custom selection pii span is only present in the backend. The obfuscation method removes the span
tag from the code that is sent to the browser.

## ems_html_encode

You can transform any text to its equivalent in html character encoding.

````twig
{{- 'text and téxt'|ems_html_encode -}}
````

See unit test for more examples.

## ems_markdown

Filter converting a Markdown text into an HTML text following the GitHub standards. 

```twig
{{ source.body|ems_markdown }}
```

## ems_stringify

Filter converting any scalar value, array or object into a string.

```twig
{{ someObject|ems_stringify }}
{{ someArray|ems_stringify }}
```
## ems_asset_average_color

Filter returning the average color, in CSS rgb format, of a passed hash.

I.e.
```twig
{{ 'ed266b89065e74483248da7ff71cb80e3cca40a5'|ems_asset_average_color}}
```

Will return `#666666`. It might be useful in order to define a background color:

```twig
{{ ems_asset_path({
    filename: 'avatar.jpg',
    sha1: avatarHash,
    mimetype: 'image/jpeg',
}, {
    _config_type: 'image',
    _resize: 'fill',
    _background: avatarHash|ems_asset_average_color,
    _width: 400,
    _height: 600,
    _quality: 80,
    _get_file_path: localPath,
}) }}
```
```twig
style="background-color: {{ avatarHash|ems_asset_average_color }}"
 ```

## ems_replace_regex

Apply php **preg_replace** function on a text string. All possible exceptions are catched and logged as warning.

Example replace all ems links by a span tag.
```twig
{% set text %}
    <h1>Example</h1>
    <p><a href="ems://object:page:dabc33113a53866fe1a1443b42a4c16d1f4bc138">Homepage</a></p>
    <p><a href="ems://object:page:7cc6310cc57818bb571e706ede0a4c10623b430c">News</a></p>
    <p>the end</p>
{% endset %}
{{ text|ems_replace_regex('/<a.*?href="ems:\\/\\/\\S+".*?>(.*?)<\\/a>', '<span>$1</span>')|raw }}
```

## ems_html_decode

Convert HTML entities  to their corresponding characters

The following example will generate a `è` :

```twig
{{ '&grave;'|ems_html_decode|json_encode|raw }}
```

### Other parameters:

 - flags: [refers to html_entity_decode's flags paramter](https://www.php.net/manual/en/function.html-entity-decode.php), default value `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`
 - encoding: [defining the encoding used when converting characters](https://www.php.net/manual/en/function.html-entity-decode.php), default value `"UTF-8""`
