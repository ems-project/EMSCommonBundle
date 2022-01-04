<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use EMS\CommonBundle\Entity\Log;

class LogRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Log::class);
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
