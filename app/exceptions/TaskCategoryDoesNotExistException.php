<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class TaskCategoryDoesNotExistException extends ExceptionTemplate
{
    public function __construct(
        int $task_id,
        int $category_id,
        ?Throwable $previous = null,
        int        $line = -1
    )
    {
        parent::__construct(
            "Task category does not exist for task_id: {$task_id} and category_id: {$category_id}",
            400,
            Severity::WARNING,
            $previous,
            $line);
    }
}