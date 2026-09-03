<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class ExpiredTokenException extends ExceptionTemplate
{
    public function __construct(?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            'Authentication token has expired',
            401,
            Severity::WARNING,
            'TOKEN_EXPIRED',
            $previous,
            $line
        );
    }
}