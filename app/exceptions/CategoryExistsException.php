<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class CategoryExistsException extends ExceptionTemplate
{
    public function __construct(string     $title,
                                ?Throwable $previous = null,
                                int        $line = -1)
    {
        parent::__construct(
            "Category with title {'$title'} already exists",
            400,
            Severity::WARNING,
            $previous,
            $line);
    }
}