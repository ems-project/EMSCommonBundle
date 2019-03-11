<?php

namespace EMS\CommonBundle\Repository;

use Doctrine\ORM\NonUniqueResultException;
use EMS\CommonBundle\Entity\AssetStorage;
use Throwable;

/**
 * AssetStorageRepository
 *
 */
class AssetStorageRepository extends \Doctrine\ORM\EntityRepository
{

     /**
     * @param string $hash
     * @param false|string $context
     * @param boolean $confirmed
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getQuery($hash, $context, $confirmed)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where($qb->expr()->eq('a.hash', ':hash'));
        $qb->andWhere($qb->expr()->eq('a.confirmed', ':confirmed'));

        if ($context) {
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

    /**
     * @param string $hash
     * @param string $context
     * @param boolean $confirmed
     * @return bool
     */
    public function head($hash, $context, $confirmed = true)
    {
        try {
            $qb = $this->getQuery($hash, $context, $confirmed)->select('count(a.hash)');
            return $qb->getQuery()->getSingleScalarResult() !== 0;
        } catch (NonUniqueResultException $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function clearCache()
    {
        try {
            $qb = $this->createQueryBuilder('asset')->delete()
                ->where('asset.context is not null');
            return $qb->getQuery()->execute() !== false;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param $hash
     * @return bool
     */
    public function removeByHash($hash): bool
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

    /**
     * @param string $hash
     * @param string $context
     * @param boolean $confirmed
     * @return null|AssetStorage
     */
    public function findByHash($hash, $context, $confirmed = true)
    {
        $qb = $this->getQuery($hash, $context, $confirmed)->select('a');

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }


    /**
     * @deprecated
     * @param $hash
     * @param $context
     * @param boolean $confirmed
     * @return mixed
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function lastUpdateDate($hash, $context, $confirmed = true)
    {
        $qb = $this->getQuery($hash, $context, $confirmed)->select('a.modified');

        $date = $qb->getQuery()->getSingleResult()['modified'];
        return $date;
    }

    /**
     * @param string $hash
     * @param string $context
     * @param boolean $confirmed
     * @return int
     */
    public function getSize($hash, $context, $confirmed = true)
    {
        try {
            $qb = $this->getQuery($hash, $context, $confirmed)->select('a.size');
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return false;
        }
    }

    /**
     * @param string $hash
     * @param string $context
     * @param boolean $confirmed
     * @return int
     */
    public function getLastUpdateDate($hash, $context, $confirmed = true)
    {
        try {
            $qb = $this->getQuery($hash, $context, $confirmed)->select('a.lastUpdateDate');
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return false;
        }
    }
}
