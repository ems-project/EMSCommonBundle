<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Entity;

interface EntityInterface
{
    /**
     * @return int|string
     */
    public function getId();

    public function getName(): string;

    public function getCreated(): \DateTime;

    public function getModified(): \DateTime;
}
