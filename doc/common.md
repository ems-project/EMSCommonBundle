# Common

## ArrayHelper

### Recursive Mapper

[EMS\CommonBundle\Common\ArrayHelper\RecursiveMapper](./../src/Common/ArrayHelper/RecursiveMapper.php)

#### mapPropertyValue

Loop recursively overall properties of an associative array and apply mapper.

```php
<?php
    use EMS\CommonBundle\Common\ArrayHelper\RecursiveMapper;

    $data = ['a' => 1, 'b' => '2', 'c' => ['c1' => 3]];
    RecursiveMapper::mapPropertyValue($data, fn (string $property, $value) => ((int) $value * 2));

    //$data containing ['a' => 2, 'b' => 4, 'c' => ['c1' => 6]]
?>
```