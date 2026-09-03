<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class InvalidTokenException extends ExceptionTemplate
{
    public function __construct(?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            'Authentication token is invalid',
            401,
            Severity::WARNING,
            'INVALID_AUTHENTICATION_TOKEN',
            $previous,
            $line
        );
    }
}