<?php

namespace EMS\CommonBundle\Elasticsearch\Exception;

class SingleResultException extends \Exception
{
    public function __construct(int $count)
    {
        parent::__construct(\sprintf('Single result exception: 1 result was expected, got %d', $count));
    }
}
