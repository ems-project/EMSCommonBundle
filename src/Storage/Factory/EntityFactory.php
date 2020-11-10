<?php

namespace EMS\CommonBundle\Storage\Factory;

use Doctrine\Bundle\DoctrineBundle\Registry;
use EMS\CommonBundle\Storage\Service\EntityStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class EntityFactory implements StorageFactoryInterface
{

    /** @var LoggerInterface */
    private $logger;
    /** @var bool */
    private $registered = false;
    /** @var Registry */
    private $doctrine;

    public function __construct(LoggerInterface $logger, Registry $doctrine)
    {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
    }

    public function createService(array $parameters): ?StorageInterface
    {
        if ('db' !== $parameters['type'] ?? null) {
            throw new \RuntimeException('The storage service type doesn\'t match \'db\'');
        }

        if ($this->registered) {
            $this->logger->warning('The entity storage service is already registered');
        }

        if (isset($parameters['activate'])) {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
        }

        if (false === $parameters['activate'] ?? true) {
            return null;
        }

         return new EntityStorage($this->doctrine);
    }

    public function getStorageType(): string
    {
        return 'db';
    }
}
