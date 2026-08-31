<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class UsernameExistsException extends ExceptionTemplate
{
    public function __construct(string $username, int $line = -1, ?Throwable $previous = null)
    {
        parent::__construct(
            "Username '{$username}' is taken by another user",
            400,
            Severity::WARNING,
            $previous,
            $line);
    }

}