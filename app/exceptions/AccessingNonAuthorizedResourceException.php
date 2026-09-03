<?php

namespace ghosty\taskmgr\exceptions;


use ghosty\taskmgr\logger\Severity;
use Throwable;

class AccessingNonAuthorizedResourceException extends ExceptionTemplate
{
    public function __construct(?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "You don't have access to this resource",
            403,
            Severity::WARNING,
            'RESOURCE ACCESS NOT ALLOWED',
            $previous,
            $line
        );
    }
}