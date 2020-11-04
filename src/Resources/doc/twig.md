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
tag from the code that is send to the browser.

## ems_html_encode

You can transform any text to its equivalent in html character encoding.

````twig
{{- 'text and téxt'|ems_html_encode -}}
````

See unit test for more examples.