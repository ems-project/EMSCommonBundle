EMSCommonBundle
=============

All code shared between the client helper and the core

Coding standards
----------------
PHP Code Sniffer is available via composer, the standard used is defined in phpcs.xml.diff:
````bash
composer phpcs
````

If your code is not compliant, you could try fixing it automatically:
````bash
composer phpcbf
````

PHPStan is run at level 2, you can check for errors locally using:
`````bash
composer phpstan
`````

Documentation
-------------

[Twig documentation](../master/Resources/doc/twig.md)