<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class TaskAssignmentDoesNotExistException extends ExceptionTemplate
{
    public function __construct(
        int $user_id,
        int $task_id,
        ?Throwable $previous = null,
        int        $line = -1
    )
    {
        parent::__construct(
            "Task assignment doesn't exist for user_id: {$user_id}, task_id: {$task_id}",
            400,
            Severity::WARNING,
            'TASK_ASSIGNMENT_DOES_NOT_EXIST',
            $previous,
            $line
        );
    }

}