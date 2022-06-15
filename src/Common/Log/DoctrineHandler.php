<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Log;

use EMS\CommonBundle\Repository\LogRepository;
use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DoctrineHandler extends AbstractProcessingHandler
{
    private LogRepository $logRepository;
    private TokenStorageInterface $tokenStorage;
    private int $minLevel;

    public function __construct(LogRepository $logRepository, TokenStorageInterface $tokenStorage, int $minLevel)
    {
        parent::__construct();

        $this->logRepository = $logRepository;
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

        $token = $this->tokenStorage->getToken();
        $record['username'] = ($token instanceof TokenInterface ? $token->getUsername() : null);
        $record['impersonator'] = ($token instanceof SwitchUserToken ? $token->getOriginalToken()->getUsername() : null);

        $this->logRepository->insertRecord($record);
    }
}
