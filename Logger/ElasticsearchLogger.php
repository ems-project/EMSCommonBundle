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
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Core\Security;

class ElasticsearchLogger extends AbstractProcessingHandler implements CacheWarmerInterface, EventSubscriberInterface
{

    /** @var Client */
    private $client;

    /** @var Security */
    private $security;

    /** @var int */
    protected $level;

    /** @var string */
    protected $instanceId;

    /** @var string */
    protected $component;

    /** @var ?string */
    protected $user;

    /** @var ?string */
    protected $impersonator;

    /** @var array */
    protected $bulk;

    /** @var bool */
    protected $tooLate;

    /** @var DateTime */
    protected $startDateTime;

    public function __construct(string $level, string $instanceId, string $component, Client $client, Security $security)
    {
        $levelName = strtoupper($level);
        if (isset(Logger::getLevels()[$levelName])) {
            $this->level = Logger::getLevels()[$levelName];
        } else {
            $this->level = Logger::INFO;
        }

        parent::__construct($this->level);
        $this->startDateTime = new DateTime();
        $this->client = $client;
        $this->instanceId = $instanceId;
        $this->client = $client;
        $this->component = $component;
        $this->security = $security;
        $this->user = null;
        $this->impersonator = null;
        $this->bulk = [];
        $this->tooLate = false;
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
                            EmsFields::LOG_USERNAME_FIELD => [
                                'type' => 'keyword',
                            ],
                            EmsFields::LOG_IMPERSONATOR_FIELD => [
                                'type' => 'keyword',
                            ],
                            EmsFields::LOG_HOST_FIELD => [
                                'type' => 'keyword',
                            ],
                            EmsFields::LOG_ROUTE_FIELD => [
                                'type' => 'keyword',
                            ],
                            EmsFields::LOG_URL_FIELD => [
                                'type' => 'text',
                                "fields" => [
                                    "raw" => [
                                        "type" =>  "keyword"
                                    ]
                                ]
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
                            EmsFields::LOG_REVISION_ID_FIELD => [
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
        if ($record['level'] >= $this->level && !$this->tooLate) {
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
                    if (in_array($key, [EmsFields::LOG_OPERATION_FIELD, EmsFields::LOG_ENVIRONMENT_FIELD, EmsFields::LOG_CONTENTTYPE_FIELD,
                        EmsFields::LOG_OUUID_FIELD, EmsFields::LOG_REVISION_ID_FIELD, EmsFields::LOG_HOST_FIELD, EmsFields::LOG_URL_FIELD,
                        EmsFields::LOG_STATUS_CODE_FIELD, EmsFields::LOG_SIZE_FIELD, EmsFields::LOG_ROUTE_FIELD, EmsFields::LOG_MICROTIME_FIELD])) {
                        $body[$key] = $value;
                    } else {
                        $body['context'][] = [
                            EmsFields::LOG_KEY_FIELD => $key,
                            EmsFields::LOG_VALUE_FIELD => $value,
                        ];
                    }
                }
            }

            if ($this->user === null && $this->security->getToken()) {
                $this->user = $this->security->getToken()->getUsername();
                if ($this->security->isGranted('ROLE_PREVIOUS_ADMIN')) {
                    foreach ($this->security->getToken()->getRoles() as $role) {
                        if ($role instanceof SwitchUserRole) {
                            $this->impersonator = $role->getSource()->getUsername();
                            break;
                        }
                    }
                }
            }
            $body[EmsFields::LOG_USERNAME_FIELD] = $this->user;
            $body[EmsFields::LOG_IMPERSONATOR_FIELD] = $this->impersonator;


            $this->bulk[] = [
                'index' => [
                    '_type' => 'doc',
                    '_index' => 'ems_internal_logger_index_'.(new DateTime())->format('Ymd'),
                ],
            ];
            $this->bulk[] = $body;

            if (count($this->bulk) > 200) {
                $this->treatBulk();
            }
        }
    }

    private function treatBulk(bool $tooLate = false)
    {
        if (!empty($this->bulk) && !$this->tooLate) {
            $this->tooLate = $tooLate;
            $this->client->bulk([
                'body' => $this->bulk,
            ]);
            $this->bulk = [];
        }
    }


    public function onKernelTerminate(PostResponseEvent $event)
    {
        switch ($event->getRequest()->getMethod()) {
            case 'POST':
            case 'PUT':
                $operation = EmsFields::LOG_OPERATION_CREATE;
                break;
            case 'GET':
                $operation = EmsFields::LOG_OPERATION_READ;
                break;
            case 'PATCH':
                $operation = EmsFields::LOG_OPERATION_UPDATE;
                break;
            case 'DELETE':
                $operation = EmsFields::LOG_OPERATION_DELETE;
                break;
            default:
                $operation = null;
        }
        $route = $event->getRequest()->attributes->get('_route', null);
        if ($operation && $route && !in_array($route, ['_wdt'])) {
            $statusCode = $event->getResponse()->getStatusCode();
            if ($statusCode < 300) {
                $level = Logger::INFO;
                $level_name = 'INFO';
            } else if ($statusCode < 400) {
                $level = Logger::NOTICE;
                $level_name = 'NOTICE';
            } else if ($statusCode < 500) {
                $level = Logger::WARNING;
                $level_name = 'WARNING';
            } else {
                $level = Logger::ERROR;
                $level_name = 'ERROR';
            }

            $record = [
                'datetime' => new \DateTime(),
                'level' => $level,
                'level_name' => $level_name,
                'channel' => 'app',
                'message' => 'app.request',
                'context' => [
                    EmsFields::LOG_OPERATION_FIELD => $operation,
                    EmsFields::LOG_HOST_FIELD => $event->getRequest()->getHost(),
                    EmsFields::LOG_URL_FIELD => $event->getRequest()->getRequestUri(),
                    EmsFields::LOG_ROUTE_FIELD => $route,
                    EmsFields::LOG_STATUS_CODE_FIELD => $statusCode,
                    EmsFields::LOG_SIZE_FIELD => strlen($event->getResponse()->getContent()),
                    EmsFields::LOG_MICROTIME_FIELD => (new DateTime())->diff($this->startDateTime)->format('%s.%F'),
                ],
            ];
            $this->write($record);
        }
        $this->treatBulk(true);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::TERMINATE => array('onKernelTerminate', -1024),
        );
    }
}
