<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class RouteAccessNotAllowed extends ExceptionTemplate
{
    public function __construct(string $route = "", ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "You don't have access to this route: {'$route'}",
            403,
            Severity::WARNING,
            'ACCESS_DENIED',
            $previous,
            $line
        );
    }
}