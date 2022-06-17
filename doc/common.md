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
```

### Log

The Common bundle provides a [Log](./../src/Entity/Log.php) entity for saving logs in the database.

You can enable the log handler with the following code:
```yaml
monolog:
    handlers:
        doctrine:
            type: service
            id: ems_common.monolog.doctrine
            channels: [app,core,audit]
```

The log handler will only store log records with a level higher then the env variable **EMS_LOG_LEVEL**.
Possible levels: 

* 100 (DEBUG)
* 200 (INFO)
* 250 (NOTICE)
* 300 (WARNING)
* 400 (ERROR)
* 500 (CRITICAL)
* 550 (ALERT)
* 600 (EMERGENCY)


## Standards

### DateTime
> Because php new DateTime can return false. This common standard will throw runtime exceptions.
```php
<?php
        use EMS\CommonBundle\Common\Standard\DateTime;

        $dateTime = DateTime::create('2018-12-31 13:05:21');
        $atomDate = DateTime::createFromFormat('2021-03-09T09:53:10+0100', \DATE_ATOM);
```

### Json
> Because php json_encode can return false and json_decode mixed. This common standard will throw runtime exceptions. 
```php
<?php
        use EMS\CommonBundle\Common\Standard\Json;
        $pretty = true;
        $encode = Json::encode(['test' => 'test'], $pretty);
        $decode = Json::decode($encode);
```
