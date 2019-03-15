<?php

namespace EMS\CommonBundle\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use EMS\CommonBundle\Entity\AssetStorage;
use Throwable;

class AssetStorageRepository extends \Doctrine\ORM\EntityRepository
{

    private function getQuery(string $hash, ?string $context, bool $confirmed): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where($qb->expr()->eq('a.hash', ':hash'));
        $qb->andWhere($qb->expr()->eq('a.confirmed', ':confirmed'));

        if ($context !== null) {
            $qb->andWhere($qb->expr()->eq('a.context', ':context'));
            $qb->setParameters([
                ':hash' => $hash,
                ':context' => $context,
                ':confirmed' => $confirmed,
            ]);
        } else {
            $qb->andWhere('a.context is null');
            $qb->setParameters([
                ':hash' => $hash,
                ':confirmed' => $confirmed,
            ]);
        }
        return $qb;
    }

    public function head(string $hash, ?string $context, bool $confirmed = true): bool
    {
        try {
            $qb = $this->getQuery($hash, $context, $confirmed)->select('count(a.hash)');
            return $qb->getQuery()->getSingleScalarResult() !== 0;
        } catch (NonUniqueResultException $e) {
            return false;
        }
    }

    public function clearCache(): bool
    {
        try {
            $qb = $this->createQueryBuilder('asset')->delete()
                ->where('asset.context is not null');
            return $qb->getQuery()->execute() !== false;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function removeByHash(string $hash): bool
    {
        try {
            $qb = $this->createQueryBuilder('asset')->delete();
            $qb->where($qb->expr()->eq('asset.hash', ':hash'));
            $qb->setParameters([
                ':hash' => $hash,
            ]);
            return $qb->getQuery()->execute() !== false;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function findByHash(string $hash, ?string $context, bool $confirmed = true): ?AssetStorage
    {
        $qb = $this->getQuery($hash, $context, $confirmed)->select('a');

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }


    /**
     * @deprecated see getLastUpdateDate()
     *
     * @return mixed
     *
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function lastUpdateDate(string $hash, ?string $context, bool $confirmed = true)
    {
        $qb = $this->getQuery($hash, $context, $confirmed)->select('a.modified');

        $date = $qb->getQuery()->getSingleResult()['modified'];
        return $date;
    }

    public function getSize(string $hash, ?string $context, bool $confirmed = true): ?int
    {
        try {
            $qb = $this->getQuery($hash, $context, $confirmed)->select('a.size');
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function getLastUpdateDate(string $hash, ?string $context, bool $confirmed = true): ?int
    {
        try {
            $qb = $this->getQuery($hash, $context, $confirmed)->select('a.lastUpdateDate');
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
