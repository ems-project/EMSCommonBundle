<?php

namespace EMS\CommonBundle\Storage\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use EMS\CommonBundle\Entity\AssetStorage;
use EMS\CommonBundle\Repository\AssetStorageRepository;
use Exception;
use Throwable;

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
        $this->repository = null;
    }

    /**
     * @inheritdoc
     */
    public function supportCacheStore(): bool
    {
        return $this->contextSupport;
    }

    /**
     * @param string $hash
     * @param string $cacheContext
     * @return bool
     */
    public function head(string $hash, ?string $cacheContext = null):bool
    {
        if (!$cacheContext || $this->contextSupport) {
            $this->init();
            try {
                return $this->repository->head($hash, $cacheContext);
            } catch (Exception $e) {
            }
        }
        return false;
    }

    /**
     *
     */
    private function init()
    {
        if (!$this->repository) {
            $this->manager = $this->doctrine->getManager();
            $this->repository = $this->manager->getRepository('EMSCommonBundle:AssetStorage');
        }
    }

    /**
     * @param string $hash
     * @param null|string $cacheContext
     * @return null|int
     */
    public function getSize(string $hash, ?string $cacheContext = null): ?int
    {
        if (!$cacheContext || $this->contextSupport) {
            $this->init();
            try {
                return $this->repository->getSize($hash, $cacheContext);
            } catch (Throwable $e) {
            }
        }
        return null;
    }


    /**
     * @param string $hash
     * @param string $filename
     * @param string|null $cacheContext
     * @return bool
     */
    public function create(string $hash, string $filename, ?string $cacheContext = null):bool
    {
        if (!$cacheContext || $this->contextSupport) {

            $entity = $this->createEntity($hash, $cacheContext);

            $entity->setSize(filesize($filename));
            $entity->setContents(file_get_contents($filename));
            $entity->setLastUpdateDate(filemtime($filename));
            $entity->setConfirmed(true);

            $this->manager->persist($entity);
            $this->manager->flush();

            return true;
        }
        return false;
    }

    private function createEntity($hash, $cacheContext = false)
    {
        /**@var AssetStorage $entity */
        $entity = $this->repository->findByHash($hash, $cacheContext);
        if (!$entity) {
            $entity = new AssetStorage();
            $entity->setHash($hash);
            $entity->setContext($cacheContext ? $cacheContext : null);
        }
        return $entity;
    }

    /**
     * @param string $hash
     * @param string|null $cacheContext
     * @param bool $confirmed
     * @return resource|null
     */
    public function read(string $hash, ?string $cacheContext = null, bool $confirmed = true)
    {
        if (!$cacheContext || $this->contextSupport) {
            $this->init();
            /**@var AssetStorage $entity */
            $entity = $this->repository->findByHash($hash, $cacheContext, $confirmed);
            if ($entity) {
                $out = $entity->getContents();

                if(is_resource($out))
                {
                    return $out;
                }
                $resource = fopen('php://memory', 'w+');
                fwrite($resource, $out);

                rewind($resource);
                return $resource;
            }
        }
        return false;
    }

    /**
     * @deprecated
     * @param string $hash
     * @param null|string $context
     * @return \DateTime|null
     */
    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime
    {
        @trigger_error("getLastUpdateDate is deprecated.", E_USER_DEPRECATED);
        if (!$context || $this->contextSupport) {
            $this->init();
            try {
                $time = $this->repository->getLastUpdateDate($hash, $context);
                return $time ? \DateTime::createFromFormat('U', $time) : null;
            } catch (Exception $e) {
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
            $this->init();
            return ($this->repository->count([]) >= 0);
        }
        catch (Exception $e)
        {
        }
        return false;
    }


    /**
     * Use to display the service in the console
     * @return string
     */
    public function __toString():string
    {
        return EntityStorage::class;
    }

    /**
     * @return bool
     */
    public function clearCache():bool
    {
        $this->init();
        return $this->repository->clearCache();
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function remove(string $hash):bool
    {
        $this->init();
        return $this->repository->removeByHash($hash);
    }

    /**
     * @param string      $hash
     * @param integer     $size
     * @param string      $name
     * @param string      $type
     * @param string|null $context
     *
     * @return bool
     */
    public function initUpload(string $hash, int $size, string $name, string $type, ?string $context = null): bool
    {

        if (!$context || $this->contextSupport) {

            $entity = $this->repository->findByHash($hash, $context, false);
            if(!$entity)
            {
                $entity = $this->createEntity($hash, $context);
            }

            $entity->setSize(0);
            $entity->setContents('');
            $entity->setLastUpdateDate(time());
            $entity->setConfirmed(false);

            $this->manager->persist($entity);
            $this->manager->flush($entity);

            return true;
        }
        return false;

    }

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return bool
     */
    public function finalizeUpload(string $hash, ?string $context = null): bool
    {
        $this->init();
        $entity = $this->repository->findByHash($hash, $context, false);
        if($entity)
        {
            $entity->setConfirmed(true);
            $entity->setSize(strlen($entity->getContents()));
            $this->manager->persist($entity);
            $this->manager->flush();
            return true;
        }
        return false;

    }



    /**
     * @param string      $hash
     * @param string      $chunk
     * @param string|null $context
     *
     * @return bool
     */
    public function addChunk(string $hash, string $chunk, ?string $context = null): bool
    {
        $this->init();
        $entity = $this->repository->findByHash($hash, $context, false);
        if($entity)
        {
            $contents = $entity->getContents();
            if(is_resource($contents))
            {
                $contents = stream_get_contents($contents);
            }

            $entity->setContents($contents.$chunk);

            $entity->setSize($entity->getSize() + strlen($chunk));
            $this->manager->persist($entity);
            $this->manager->flush($entity);
            return true;
        }
        return false;
    }
}
