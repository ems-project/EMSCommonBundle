<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Factory;

use Doctrine\Bundle\DoctrineBundle\Registry;
use EMS\CommonBundle\Storage\Service\EntityStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /**
     * @param array<string, mixed> $parameters
     */
    public function createService(array $parameters): ?StorageInterface
    {
        $config = $this->resolveParameters($parameters);

        if (false === $config[self::STORAGE_CONFIG_ACTIVATE] ?? true) {
            return null;
        }

        if ($this->registered) {
            $this->logger->warning('The entity storage service is already registered');

            return null;
        }
        $this->registered = true;

        return new EntityStorage($this->doctrine);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{type: string, activate: bool}
     */
    private function resolveParameters(array $parameters): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
                self::STORAGE_CONFIG_ACTIVATE => true,
            ])
            ->setAllowedTypes(self::STORAGE_CONFIG_TYPE, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_ACTIVATE, 'bool')
            ->setRequired(self::STORAGE_CONFIG_TYPE)
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
            ->setAllowedValues(self::STORAGE_CONFIG_ACTIVATE, [true, false])
        ;

        /** @var array{type: string, activate: bool} $resolvedParameter */
        $resolvedParameter = $resolver->resolve($parameters);

        return $resolvedParameter;
    }
}
