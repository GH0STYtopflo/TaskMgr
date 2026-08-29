<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class UpdatingTaskStatusToSubmittedException extends ExceptionTemplate
{
    public function __construct(?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Cannot update task status to 'SUBMITTED'",
            400,
            Severity::WARNING,
            $previous,
            $line);
    }
}