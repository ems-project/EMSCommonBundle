<?php

namespace EMS\CommonBundle\Storage\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use EMS\CommonBundle\Entity\AssetStorage;
use EMS\CommonBundle\Repository\AssetStorageRepository;
use Exception;
use function file_get_contents;
use function filemtime;
use function filesize;

class EntityStorage implements StorageInterface
{

    /**@var Registry $doctrine */
    private $doctrine;
    /**@var ObjectManager */
    private $manager;
    /**@var AssetStorageRepository */
    private $repository;
    /**@var bool */
    private $contextSupport;

    /**
     * EntityStorage constructor.
     * @param Registry $doctrine
     * @param bool $contextSupport
     */
    public function __construct(Registry $doctrine, bool $contextSupport)
    {
        $this->doctrine = $doctrine;
        $this->contextSupport = $contextSupport;
        $this->repository = false;
    }

    /**
     * @return bool
     */
    public function supportCacheStore()
    {
        return $this->contextSupport;
    }

    /**
     * @param string $hash
     * @param bool|string $cacheContext
     * @return bool
     */
    public function head($hash, $cacheContext = false)
    {
        if ($cacheContext === false || $this->contextSupport) {
            $this->init();
            try {
                return $this->repository->head($hash, $cacheContext);
            } catch (NonUniqueResultException $e) {
            }
        }
        return false;
    }

    /**
     *
     */
    private function init()
    {
        if ($this->repository === false) {
            $this->manager = $this->doctrine->getManager();
            $this->repository = $this->manager->getRepository('EMSCommonBundle:AssetStorage');
        }
    }

    /**
     * @param string $hash
     * @param bool|string $cacheContext
     * @return bool|int
     */
    public function getSize($hash, $cacheContext = false)
    {
        if ($cacheContext === false || $this->contextSupport) {
            $this->init();
            try {
                return $this->repository->getSize($hash, $cacheContext);
            } catch (NonUniqueResultException $e) {
            }
        }
        return false;
    }



    /**
     * @param string $hash
     * @param string $filename
     * @param bool|string $cacheContext
     * @return bool
     */
    public function create($hash, $filename, $cacheContext = false)
    {
        if ($cacheContext === false || $this->contextSupport) {

            $this->init();

            /**@var AssetStorage $entity */
            $entity = $this->repository->findByHash($hash, $cacheContext);
            if ($entity) {
                $entity->setLastUpdateDate(filemtime($filename));
                $entity->setSize(filesize($filename));
                $entity->setContents(file_get_contents($filename));
            }
            else{
                $entity = new AssetStorage();
                $entity->setHash($hash);
                $entity->setContext($cacheContext ? $cacheContext : null);
                $entity->setLastUpdateDate(filemtime($filename));
                $entity->setSize(filesize($filename));
                $entity->setContents(file_get_contents($filename));
            }
            $this->manager->persist($entity);
            $this->manager->flush();

            return true;
        }
        return false;
    }

    /**
     * @param string $hash
     * @param bool|string $cacheContext
     * @return bool|resource
     */
    public function read($hash, $cacheContext = false)
    {
        if ($cacheContext === false || $this->contextSupport) {
            $this->init();
            /**@var AssetStorage $entity */
            $entity = $this->repository->findByHash($hash, $cacheContext);
            if ($entity) {
                return $entity->getContents();
            }
        }
        return false;
    }

    /**
     * @param string $hash
     * @param bool|string $cacheContext
     * @return bool|int
     */
    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime
    {
        if ($context === false || $this->contextSupport) {
            $this->init();
            try {
                return $this->repository->lastUpdateDate($hash, $context);
            } catch (NoResultException $e) {
            }
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function health(): bool
    {
        try
        {
            return ($this->repository->count() >= 0);
        }
        catch (Exception $e)
        {
        }
        return false;
    }


    public function __toString()
    {
        return EntityStorage::class;
    }

    /**
     * @return bool
     */
    public function clearCache()
    {
        $this->init();
        return $this->repository->clearCache();
    }

    /**
     * @param $hash
     * @return bool
     */
    public function remove($hash)
    {
        $this->init();
        return $this->repository->removeByHash($hash);
    }
}
