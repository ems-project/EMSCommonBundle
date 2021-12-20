<?php

namespace EMS\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Analyzer.
 *
 * @ORM\Table(name="asset_storage")
 * @ORM\Entity(repositoryClass="EMS\CommonBundle\Repository\AssetStorageRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class AssetStorage implements EntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime")
     */
    private $modified;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=1024, unique=true)
     */
    private $hash;

    /**
     * @var string|resource
     *
     * @ORM\Column(name="contents", type="blob")
     */
    private $contents;

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="bigint")
     */
    private $size;

    /**
     * @var bool
     *
     * @ORM\Column(name="confirmed", type="boolean", options={"default" : 0})
     */
    private $confirmed;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateModified(): void
    {
        $this->modified = new \DateTime();
        if (!isset($this->created)) {
            $this->created = $this->modified;
        }
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): AssetStorage
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string|resource
     */
    public function getContents()
    {
        return $this->contents;
    }

    public function setContents(string $contents): AssetStorage
    {
        $this->contents = $contents;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCreated(\DateTime $created): AssetStorage
    {
        $this->created = $created;

        return $this;
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setModified(\DateTime $modified): AssetStorage
    {
        $this->modified = $modified;

        return $this;
    }

    public function getModified(): \DateTime
    {
        return $this->modified;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): AssetStorage
    {
        $this->size = $size;

        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): AssetStorage
    {
        $this->confirmed = $confirmed;

        return $this;
    }
}
