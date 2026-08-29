<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class DatabaseException extends ExceptionTemplate
{
    public function __construct(string     $message = "",
                                int        $code = 0,
                                Severity   $severity = Severity::INFO,
                                ?Throwable $previous = null,
                                int        $line = -1)
    {
        parent::__construct($message, $code, $severity, $previous, $line);
    }
}