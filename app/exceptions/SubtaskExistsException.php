<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class SubtaskExistsException extends ExceptionTemplate
{
    public function __construct(
        string     $title = "",
        ?Throwable $previous = null,
        int        $line = -1)
    {
        parent::__construct(
            "Subtask already exists for title: {$title}",
            409,
            Severity::WARNING,
            'DUPLICATE_SUBTASK',
            $previous,
            $line
        );
    }

}