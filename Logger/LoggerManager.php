<?php


namespace EMS\CommonBundle\Logger;

use DateTime;
use Elasticsearch\Client;
use EMS\CommonBundle\Helper\EmsFields;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LoggerManager extends AbstractProcessingHandler implements CacheWarmerInterface, EventSubscriberInterface
{

    /** @var Client */
    private $client;

    /** @var int */
    protected $level;

    /** @var string */
    protected $instanceId;

    /** @var string */
    protected $component;

    /** @var array */
    protected $bulk;

    public function __construct(string $level, string $instanceId, string $component, Client $client)
    {
        $this->client = $client;
        $this->instanceId = $instanceId;
        $this->client = $client;
        $this->component = $component;
        $this->bulk = [];
        $levelName = strtoupper($level);
        if (isset(Logger::getLevels()[$levelName])) {
            $this->level = Logger::getLevels()[$levelName];
        } else {
            $this->level = Logger::NOTICE;
        }
    }

    public function warmUp($cacheDir)
    {
        if ($this->client->indices()->existsTemplate([
            'name' => 'ems_internal_logger_template',
        ])) {
            $this->client->indices()->deleteTemplate([
                'name' => 'ems_internal_logger_template',
            ]);
        }

        $this->client->indices()->putTemplate([
            'name' => 'ems_internal_logger_template',
            'body' => [
                'template' => 'ems_internal_logger_index_*',
                'aliases' => ['ems_internal_logger_alias' => (object) array()],
                'mappings' => [
                    'doc' => [
                        'properties' => [
                            'channel' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'component' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            EmsFields::LOG_CONTENTTYPE_FIELD  => [
                                'type' => 'keyword',
                            ],
                            EmsFields::LOG_OUUID_FIELD => [
                                'type' => 'keyword',
                            ],
                            EmsFields::LOG_ENVIRONMENT_FIELD => [
                                'type' => 'keyword',
                            ],
                            EmsFields::LOG_OPERATION_FIELD => [
                                'type' => 'keyword',
                            ],
                            'instance_id' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'level_name' => [
                                'type' => 'keyword',
                                'ignore_above' => 30,
                            ],
                            'datetime' => [
                                'type' => 'date',
                                'format' => 'date_time_no_millis',
                            ],
                            'level' => [
                                'type' => 'long',
                            ],
                            'message' => [
                                'type' => 'text',
                            ],
                            'context' => [
                                'type' => 'nested',
                                'properties' => [
                                    'key' => [
                                        'type' => 'keyword',
                                        'ignore_above' => 256,
                                    ],
                                    'value' => [
                                        'type' => 'text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function isOptional()
    {
        return false;
    }

    protected function write(array $record)
    {
        if ($record['level'] >= $this->level) {
            $datetime = null;
            if ($record['datetime'] instanceof \DateTime) {
                $datetime = $record['datetime']->format('c');
            }
            $body = [
                'level_name' => $record['level_name'],
                'level' => $record['level'],
                'message' => $record['message'],
                'channel' => $record['channel'],
                'datetime' => $datetime,
            ];
            $body['instance_id'] = $this->instanceId;
            $body['component'] = $this->component;
            $body['context'] = [];
            unset($body['formatted']);

            foreach ($record['context'] as $key => &$value) {
                if (!is_object($value)) {
                    if (in_array($key, [EmsFields::LOG_OPERATION_FIELD, EmsFields::LOG_ENVIRONMENT_FIELD, EmsFields::LOG_CONTENTTYPE_FIELD, EmsFields::LOG_OUUID_FIELD])) {
                        $body[$key] = $value;
                    } else {
                        $body['context'][] = [
                            EmsFields::LOG_KEY_FIELD => $key,
                            EmsFields::LOG_VALUE_FIELD => $value,
                        ];
                    }
                }
            }

            $this->bulk[] = [
                'index' => [
                    '_type' => 'doc',
                    '_index' => 'ems_internal_logger_index_'.(new DateTime())->format('Ymd'),
                ],
            ];
            $this->bulk[] = $body;
        }
    }

    private function treatBulk()
    {
        if (!empty($this->bulk)) {
            $this->client->bulk([
                'body' => $this->bulk,
            ]);
            $this->bulk = [];
        }
    }


    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->treatBulk();
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::TERMINATE => array('onKernelTerminate', -1024),
        );
    }
}
