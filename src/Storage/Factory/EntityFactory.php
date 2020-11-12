<?php

namespace EMS\CommonBundle\Storage\Factory;

use Doctrine\Bundle\DoctrineBundle\Registry;
use EMS\CommonBundle\Storage\Service\EntityStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class EntityFactory implements StorageFactoryInterface
{
    /** @var string */
    const STORAGE_TYPE = 'db';
    /** @var string */
    const STORAGE_CONFIG_ACTIVATE = 'activate';
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
        if (self::STORAGE_TYPE !== $parameters[StorageFactoryInterface::STORAGE_CONFIG_TYPE] ?? null) {
            throw new \RuntimeException(sprintf('The storage service type doesn\'t match \'%s\'', self::STORAGE_TYPE));
        }

        if ($this->registered) {
            $this->logger->warning('The entity storage service is already registered');
        }

        if (isset($parameters[self::STORAGE_CONFIG_ACTIVATE])) {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
        }

        if (false === $parameters[self::STORAGE_CONFIG_ACTIVATE] ?? true) {
            return null;
        }

         return new EntityStorage($this->doctrine);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }

    /**
     * @return array<mixed>
     */
    public static function getDefaultParameters(): array
    {
        return [
            self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
            self::STORAGE_CONFIG_ACTIVATE => true,
        ];
    }
}
