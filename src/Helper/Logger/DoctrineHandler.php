<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Helper\Logger;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Ramsey\Uuid\Uuid;
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

        $token = $this->tokenStorage->getToken();
        $username = null;
        if ($token instanceof TokenInterface) {
            $username = $token->getUsername();
        }
        $impersonator = null;
        if ($token instanceof SwitchUserToken) {
            $impersonator = $record['impersonator'] = $token->getOriginalToken()->getUsername();
        }

        $stmt = $this->entityManager->getConnection()->prepare('INSERT INTO log_message (id, created, modified, message, context, level, level_name, channel, extra, formatted, username, impersonator) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bindValue(1, Uuid::uuid1()->toString(), Types::STRING);
        $stmt->bindValue(2, $record['datetime'], Types::DATETIME_MUTABLE);
        $stmt->bindValue(3, $record['datetime'], Types::DATETIME_MUTABLE);
        $stmt->bindValue(4, $record['message'], Types::TEXT);
        $stmt->bindValue(5, $record['context'], Types::JSON);
        $stmt->bindValue(6, $record['level'], Types::SMALLINT);
        $stmt->bindValue(7, $record['level_name'], Types::STRING);
        $stmt->bindValue(8, $record['channel'], Types::STRING);
        $stmt->bindValue(9, $record['extra'], Types::JSON);
        $stmt->bindValue(10, $record['formatted'], Types::TEXT);
        $stmt->bindValue(11, $username, Types::STRING);
        $stmt->bindValue(12, $impersonator, Types::STRING);
        $stmt->executeQuery();
    }
}
