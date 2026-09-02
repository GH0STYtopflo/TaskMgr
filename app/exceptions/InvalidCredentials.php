<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class InvalidCredentials extends ExceptionTemplate
{
    public function __construct(?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Invalid username and/or password provided",
            401,
            Severity::WARNING,
            'INVALID_CREDENTIALS',
            $previous,
            $line
        );
    }

}