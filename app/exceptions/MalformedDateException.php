<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class MalformedDateException extends ExceptionTemplate
{
    public function __construct(string $malformed = "", ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Malformed date {'$malformed'}",
            400,
            Severity::WARNING,
            $previous,
            $line
        );
    }
}