<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class TokenNotProvidedException extends ExceptionTemplate
{
    public function __construct(?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Authentication token not provided",
            401,
            Severity::WARNING,
            'MISSING_AUTHENTICATION_TOKEN',
            $previous,
            $line
        );
    }

}