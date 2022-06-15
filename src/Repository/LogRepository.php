<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use EMS\CommonBundle\Entity\Log;
use EMS\CommonBundle\Helper\EmsFields;
use Ramsey\Uuid\Uuid;

/**
 * @extends ServiceEntityRepository<Log>
 */
class LogRepository extends ServiceEntityRepository
{
    private Connection $connection;

    private const COLUMN_TYPES = [
        'id' => Types::STRING,
        'created' => Types::DATETIME_MUTABLE,
        'modified' => Types::DATETIME_MUTABLE,
        'message' => Types::TEXT,
        'context' => Types::JSON,
        'level' => Types::SMALLINT,
        'level_name' => Types::STRING,
        'channel' => Types::STRING,
        'extra' => Types::JSON,
        'formatted' => Types::TEXT,
        'username' => Types::STRING,
        'impersonator' => Types::STRING,
        'ouuid' => Types::STRING,
    ];

    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Log::class);

        $this->connection = $this->getEntityManager()->getConnection();
    }

    /**
     * @param array<mixed> $record
     */
    public function insertRecord(array $record): void
    {
        $query = <<<QUERY
            INSERT INTO log_message (
                id, created, modified, message, context, 
                level, level_name, channel, extra, formatted, 
                username, impersonator, ouuid
            ) VALUES (
                :id, :created, :modified, :message, :context, 
                :level, :level_name, :channel, :extra, :formatted, 
                :username, :impersonator, :ouuid);
QUERY;

        $record['id'] = Uuid::uuid1()->toString();
        $record['created'] = $record['datetime'];
        $record['modified'] = $record['datetime'];
        $record['ouuid'] = $record['context'][EmsFields::LOG_OUUID_FIELD] ?? null;

        $stmt = $this->connection->prepare($query);
        foreach (self::COLUMN_TYPES as $name => $type) {
            $stmt->bindValue(':'.$name, ($record[$name] ?? null), $type);
        }
        $stmt->executeStatement();
    }

    public function clearLogs(\DateTime $before): bool
    {
        try {
            $qb = $this->createQueryBuilder('log')
                ->delete()
                ->where('log.created < :before')
                ->setParameter('before', $before);

            return false !== $qb->getQuery()->execute();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
