<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Helper\Logger;

use Doctrine\ORM\EntityManagerInterface;
use EMS\CommonBundle\Entity\Log;
use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DoctrineHandler extends AbstractProcessingHandler
{
    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;
    private int $minLevel;

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, int $minLevel)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->minLevel = $minLevel;
    }

    /**
     * @param array{message: string, level: int, level_name: string, context: array, channel: string, formatted: string, datetime: \DateTimeImmutable, extra: array} $record
     */
    protected function write(array $record): void
    {
        if ($record['level'] < $this->minLevel) {
            return;
        }

        $log = new Log();
        $log->setMessage($record['message']);
        $log->setContext($record['context']);
        $log->setLevel($record['level']);
        $log->setLevelName($record['level_name']);
        $log->setChannel($record['channel']);
        $log->setExtra($record['extra']);
        $log->setFormatted($record['formatted']);

        $token = $this->tokenStorage->getToken();
        if ($token instanceof TokenInterface) {
            $log->setUsername($token->getUsername());
        }
        if ($token instanceof SwitchUserToken) {
            $log->setImpersonator($token->getOriginalToken()->getUsername());
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
