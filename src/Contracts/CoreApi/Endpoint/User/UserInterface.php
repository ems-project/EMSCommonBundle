<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi\Endpoint\User;

interface UserInterface
{
    /**
     * @return ProfileInterface[]
     */
    public function getProfiles(): array;

    public function getProfileAuthenticated(): ProfileInterface;
}
