<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class TaskHasActiveSubtasksException extends ExceptionTemplate
{
    public function __construct(int $id, ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "task with id {$id} has active subtask(s). Finish them first!",
            409,
            Severity::WARNING,
            'TASK_HAS_ACTIVE_SUBTASK',
            $previous,
            $line
        );
    }

}