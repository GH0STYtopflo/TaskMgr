<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class UsernameExistsException extends ExceptionTemplate
{
    public function __construct(string $username, int $line = -1, ?Throwable $previous = null)
    {
        parent::__construct(
            "Username '{$username}' is not available",
            409,
            Severity::WARNING,
            'USERNAME_EXISTS',
            $previous,
            $line
        );
    }

}