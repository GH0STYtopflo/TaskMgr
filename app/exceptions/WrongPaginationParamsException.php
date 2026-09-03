<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class WrongPaginationParamsException extends ExceptionTemplate
{
    public function __construct(?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Pagination params are out of range",
            400,
            Severity::WARNING,
            'WRONG_PAGINATION_PARAMS',
            $previous,
            $line
        );
    }
}