<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class UserAlreadyAssignedException extends ExceptionTemplate
{
    public function __construct(int $userId, int $taskId, ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "User {'$userId'} already assigned to task {'$taskId'}",
            409,
            Severity::WARNING,
            'USER_ALREADY_ASSIGNED',
            $previous,
            $line);
    }

}