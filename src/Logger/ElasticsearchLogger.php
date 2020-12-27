<?php

namespace EMS\CommonBundle\Logger;

use DateTime;
use Elastica\Bulk;
use Elastica\Bulk\Action;
use Elastica\Client;
use Elasticsearch\Endpoints\Indices\Template\Delete;
use Elasticsearch\Endpoints\Indices\Template\Exists;
use Elasticsearch\Endpoints\Indices\Template\Put;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;
use EMS\CommonBundle\Elasticsearch\Mapping;
use EMS\CommonBundle\Helper\EmsFields;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Core\Security;

class ElasticsearchLogger extends AbstractProcessingHandler implements CacheWarmerInterface, EventSubscriberInterface
{
    /** @var string */
    private const EMS_INTERNAL_LOGGER_INDEX_PATTERN = 'ems_internal_logger_index_*';
    /** @var string */
    public const EMS_INTERNAL_LOGGER_ALIAS = 'ems_internal_logger_alias';
    /** @var string */
    private const EMS_INTERNAL_LOGGER_INDEX = 'ems_internal_logger_index_';
    /** @var string */
    private const EMS_INTERNAL_LOGGER_TEMPLATE = 'ems_internal_logger_template';
    /** @var string */
    private const EMS_LOGS = 'ems__logs';

    /** @var Client */
    private $client;
    /** @var Security */
    private $security;
    /** @var Mapping */
    private $mapping;
    /** @var int */
    protected $level;
    /** @var string */
    protected $instanceId;
    /** @var string */
    protected $version;
    /** @var string */
    protected $component;
    /** @var ?string */
    protected $user;
    /** @var ?string */
    protected $impersonator;
    /** @var Bulk */
    private $bulk;
    /** @var bool */
    protected $tooLate;
    /** @var float */
    protected $startMicrotime;
    /** @var bool */
    protected $byPass;
    /** @var string */
    private $contentTypeName;

    public function __construct(string $level, string $instanceId, string $version, string $component, Client $client, Security $security, Mapping $mapping, bool $byPass = false, string $contentTypeName = self::EMS_LOGS)
    {
        $levelName = \strtoupper($level);
        if (isset(Logger::getLevels()[$levelName])) {
            $this->level = Logger::getLevels()[$levelName];
        } else {
            $this->level = Logger::INFO;
        }

        parent::__construct($this->level);
        $this->startMicrotime = \microtime(true);
        $this->client = $client;
        $this->mapping = $mapping;
        $this->instanceId = $instanceId;
        $this->version = $version;
        $this->component = $component;
        $this->security = $security;
        $this->user = null;
        $this->impersonator = null;
        $this->bulk = new Bulk($this->client);
        $this->tooLate = false;
        $this->byPass = $byPass;
        $this->contentTypeName = $contentTypeName;
    }

    public function warmUp($cacheDir): void
    {
        if ($this->byPass) {
            return;
        }

        try {
            $templateExistsEndpoint = new Exists();
            $templateExistsEndpoint->setName(self::EMS_INTERNAL_LOGGER_TEMPLATE);
            $response = $this->client->requestEndpoint($templateExistsEndpoint);
            if ($response->isOk()) {
                $deleteTemplate = new Delete();
                $deleteTemplate->setName(self::EMS_INTERNAL_LOGGER_TEMPLATE);
                $this->client->requestEndpoint($deleteTemplate);
            }
            $mapping = [
                'channel' => $this->mapping->getLimitedKeywordMapping(),
                'component' => $this->mapping->getLimitedKeywordMapping(),
                EmsFields::LOG_CONTENTTYPE_FIELD => $this->mapping->getKeywordMapping(),
                EmsFields::LOG_OUUID_FIELD => $this->mapping->getKeywordMapping(),
                EmsFields::LOG_ENVIRONMENT_FIELD => $this->mapping->getKeywordMapping(),
                EmsFields::LOG_OPERATION_FIELD => $this->mapping->getKeywordMapping(),
                EmsFields::LOG_USERNAME_FIELD => $this->mapping->getKeywordMapping(),
                EmsFields::LOG_IMPERSONATOR_FIELD => $this->mapping->getKeywordMapping(),
                EmsFields::LOG_HOST_FIELD => $this->mapping->getKeywordMapping(),
                EmsFields::LOG_ROUTE_FIELD => $this->mapping->getKeywordMapping(),
                EmsFields::LOG_URL_FIELD => $this->mapping->getTextWithSubRawMapping(),
                'instance_id' => $this->mapping->getLimitedKeywordMapping(),
                EmsFields::LOG_SESSION_ID_FIELD => $this->mapping->getKeywordMapping(),
                'version' => $this->mapping->getLimitedKeywordMapping(),
                'level_name' => $this->mapping->getKeywordMapping(),
                'datetime' => $this->mapping->getDateTimeMapping(),
                'level' => $this->mapping->getLongMapping(),
                EmsFields::LOG_REVISION_ID_FIELD => $this->mapping->getLongMapping(),
                EmsFields::LOG_MICROTIME_FIELD => $this->mapping->getFloatMapping(),
                'message' => $this->mapping->getTextMapping(),
                'context' => [
                    'type' => 'nested',
                    'properties' => [
                        'key' => $this->mapping->getLimitedKeywordMapping(),
                        'value' => $this->mapping->getTextMapping(),
                    ],
                ],
            ];

            $mapping = \array_merge($mapping, $this->mapping->defaultMapping());

            if (\version_compare($this->client->getVersion(), '7') >= 0) {
                $body = [
                    'index_patterns' => [self::EMS_INTERNAL_LOGGER_INDEX_PATTERN],
                    'aliases' => [self::EMS_INTERNAL_LOGGER_ALIAS => (object) []],
                    'mappings' => [
                        'properties' => $mapping,
                    ],
                ];
            } else {
                $body = [
                    'template' => self::EMS_INTERNAL_LOGGER_INDEX_PATTERN,
                    'aliases' => [self::EMS_INTERNAL_LOGGER_ALIAS => (object) []],
                    'mappings' => [
                        'doc' => [
                            'properties' => $mapping,
                        ],
                    ],
                ];
            }

            $putTemplateEndpoint = new Put();
            $putTemplateEndpoint->setName(self::EMS_INTERNAL_LOGGER_TEMPLATE);
            $putTemplateEndpoint->setBody($body);
            $this->client->requestEndpoint($putTemplateEndpoint);
        } catch (\Throwable $e) {
            // the cluster might be available only in read only (dev behind a reverse proxy)
        }
    }

    public function isOptional()
    {
        return false;
    }

    /**
     * @param array<mixed> $record
     */
    protected function write(array $record): void
    {
        if ($this->byPass) {
            return;
        }

        try {
            $this->writeRecord($record);
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param array<mixed> $record
     */
    protected function writeRecord(array $record): void
    {
        if ($record[EmsFields::LOG_LEVEL_FIELD] >= $this->level && !$this->tooLate) {
            $datetime = null;
            if ($record[EmsFields::LOG_DATETIME_FIELD] instanceof \DateTime) {
                $datetime = $record[EmsFields::LOG_DATETIME_FIELD]->format('c');
            }
            $body = [
                EMSSource::FIELD_CONTENT_TYPE => $this->contentTypeName,
                EmsFields::LOG_LEVEL_NAME_FIELD => $record[EmsFields::LOG_LEVEL_NAME_FIELD],
                EmsFields::LOG_LEVEL_FIELD => $record[EmsFields::LOG_LEVEL_FIELD],
                EmsFields::LOG_MESSAGE_FIELD => $record[EmsFields::LOG_MESSAGE_FIELD],
                EmsFields::LOG_CHANNEL_FIELD => $record[EmsFields::LOG_CHANNEL_FIELD],
                EmsFields::LOG_DATETIME_FIELD => $datetime,
                EmsFields::LOG_INSTANCE_ID_FIELD => $this->instanceId,
                EmsFields::LOG_VERSION_FIELD => $this->version,
                EmsFields::LOG_COMPONENT_FIELD => $this->component,
                EmsFields::LOG_CONTEXT_FIELD => [],
            ];

            foreach ($record[EmsFields::LOG_CONTEXT_FIELD] as $key => &$value) {
                if (!\is_object($value)) {
                    if (\in_array($key, [EmsFields::LOG_OPERATION_FIELD, EmsFields::LOG_ENVIRONMENT_FIELD, EmsFields::LOG_CONTENTTYPE_FIELD,
                        EmsFields::LOG_OUUID_FIELD, EmsFields::LOG_REVISION_ID_FIELD, EmsFields::LOG_HOST_FIELD, EmsFields::LOG_URL_FIELD,
                        EmsFields::LOG_STATUS_CODE_FIELD, EmsFields::LOG_SIZE_FIELD, EmsFields::LOG_ROUTE_FIELD, EmsFields::LOG_MICROTIME_FIELD,
                        EmsFields::LOG_SESSION_ID_FIELD, ])
                    ) {
                        $body[$key] = $value;
                    } else {
                        $body[EmsFields::LOG_CONTEXT_FIELD][] = [
                            EmsFields::LOG_KEY_FIELD => $key,
                            EmsFields::LOG_VALUE_FIELD => $value,
                        ];
                    }
                }
            }

            if (null === $this->user && null !== $this->security->getToken()) {
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

            $action = new Action();
            $action->setIndex(self::EMS_INTERNAL_LOGGER_INDEX.(new DateTime())->format('Ymd'));

            if (\version_compare($this->client->getVersion(), '7') < 0) {
                $action->setType('doc');
            }
            $action->setOpType(Action::OP_TYPE_INDEX);
            $action->setSource($body);

            $this->bulk->addAction($action);
            if (\count($this->bulk->getActions()) > 200) {
                $this->treatBulk();
            }
        }
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $commandObject = $event->getCommand();
        if (null === $commandObject) {
            $this->treatBulk();

            return;
        }

        $command = \implode(' ', $event->getInput()->getArguments());

        foreach ($event->getInput()->getOptions() as $id => $value) {
            if ($commandObject->getDefinition()->getOption($id)->getDefault() !== $value) {
                if (\is_bool($value) || null === $value) {
                    $command .= \sprintf(' --%s', $id);
                } elseif (\is_string($value)) {
                    $command .= \sprintf(' --%s=%s', $id, $value);
                } elseif (\is_int($value)) {
                    $command .= \sprintf(' --%s=%d', $id, $value);
                } elseif (\is_array($value)) {
                    $command .= \sprintf(' --%s=%s', $id, \implode('|', $value));
                } else {
                    $command .= \sprintf(' (Unsupported value format for option %s)', $id);
                }
            }
        }
        $record = [
            'datetime' => new \DateTime(),
            'level' => 0 === $event->getExitCode() ? Logger::INFO : Logger::ERROR,
            'level_name' => 0 === $event->getExitCode() ? 'INFO' : 'ERROR',
            'channel' => 'app',
            'message' => 'app.command',
            'context' => [
                EmsFields::LOG_OPERATION_FIELD => EmsFields::LOG_OPERATION_UPDATE,
                EmsFields::LOG_EXIT_CODE => $event->getExitCode(),
                EmsFields::LOG_COMMAND_NAME => $commandObject->getName(),
                EmsFields::LOG_COMMAND_LINE => $command,
            ],
        ];
        $this->write($record);

        $this->treatBulk();
    }

    private function treatBulk(bool $tooLate = false): void
    {
        if ($this->byPass || $this->tooLate) {
            return;
        }

        $this->tooLate = $tooLate;
        if (\count($this->bulk->getActions()) > 0) {
            try {
                $this->bulk->send();
            } catch (\Throwable $e) {
                // the cluster might be available only in read only (dev behind a reverse proxy)
            }
        }
    }

    public function onKernelTerminate(PostResponseEvent $event): void
    {
        if ($this->byPass) {
            return;
        }

        $request = $event->getRequest();
        switch ($request->getMethod()) {
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
        $route = $request->attributes->get('_route', null);
        if ($operation && $route && !\in_array($route, ['_wdt'])) {
            $statusCode = $event->getResponse()->getStatusCode();
            if ($statusCode < 300) {
                $level = Logger::INFO;
                $level_name = 'INFO';
            } elseif ($statusCode < 400) {
                $level = Logger::NOTICE;
                $level_name = 'NOTICE';
            } elseif ($statusCode < 500) {
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
                    EmsFields::LOG_HOST_FIELD => $request->getHost(),
                    EmsFields::LOG_URL_FIELD => $request->getRequestUri(),
                    EmsFields::LOG_ROUTE_FIELD => $route,
                    EmsFields::LOG_STATUS_CODE_FIELD => $statusCode,
                    EmsFields::LOG_SIZE_FIELD => \strlen((string) $event->getResponse()->getContent()),
                    EmsFields::LOG_MICROTIME_FIELD => (\microtime(true) - $this->startMicrotime),
                ],
            ];
            if ($request->hasSession()) {
                $record['context'][EmsFields::LOG_SESSION_ID_FIELD] = $request->getSession()->getId();
            }
            $this->write($record);
        }
        $this->treatBulk(true);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => ['onKernelTerminate', -1024],
        ];
    }
}
