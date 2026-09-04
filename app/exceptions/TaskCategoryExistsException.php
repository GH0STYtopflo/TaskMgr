<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class TaskCategoryExistsException extends ExceptionTemplate
{
    public function __construct(int $taskId, int $categoryId, ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Task {$taskId} already has category {$categoryId}",
            409,
            Severity::WARNING,
            'TASK_CATEGORY_EXISTS',
            $previous,
            $line);
    }

}