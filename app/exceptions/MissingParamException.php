<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class MissingParamException extends ExceptionTemplate
{
    public function __construct(string $missing, ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Missing argument {'$missing'}",
            400,
            Severity::WARNING,
            $previous,
            $line
        );
    }
}