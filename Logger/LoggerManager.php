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

class LoggerManager extends AbstractProcessingHandler implements CacheWarmerInterface, EventSubscriberInterface
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

    public function __construct(string $level, string $instanceId, string $component, Client $client, Security $security)
    {
        $this->client = $client;
        $this->instanceId = $instanceId;
        $this->client = $client;
        $this->component = $component;
        $this->security = $security;
        $this->user = null;
        $this->impersonator = null;
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
                            EmsFields::LOG_USERNAME_FIELD => [
                                'type' => 'keyword',
                            ],
                            EmsFields::LOG_IMPERSONATOR_FIELD => [
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
                    if (in_array($key, [EmsFields::LOG_OPERATION_FIELD, EmsFields::LOG_ENVIRONMENT_FIELD, EmsFields::LOG_CONTENTTYPE_FIELD, EmsFields::LOG_OUUID_FIELD, EmsFields::LOG_REVISION_ID_FIELD])) {
                        $body[$key] = $value;
                    } else {
                        $body['context'][] = [
                            EmsFields::LOG_KEY_FIELD => $key,
                            EmsFields::LOG_VALUE_FIELD => $value,
                        ];
                    }
                }
            }

            if($this->user === null){
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
