<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class RouteNotFoundException extends ExceptionTemplate
{
    public function __construct(string $reqRoute, string $method, ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Request route '{$method} {$reqRoute}' not found",
            404,
            Severity::WARNING,
            'INVALID_REQUEST_ROUTE',
            $previous,
            $line
        );
    }

}