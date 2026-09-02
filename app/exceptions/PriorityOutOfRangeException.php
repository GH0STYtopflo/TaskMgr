<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class PriorityOutOfRangeException extends ExceptionTemplate
{
    /**
     * @param int $provided
     * @param Throwable|null $previous
     * @param int $line
     */
    public function __construct(
        int        $provided,
        ?Throwable $previous = null,
        int        $line = -1
    )
    {
        parent::__construct(
            "Priority must be an integer between 1 and 20. $provided given.",
            400,
            Severity::WARNING,
            'PRIORITY_OUT_OF_RANGE',
            $previous,
            $line);
    }
}