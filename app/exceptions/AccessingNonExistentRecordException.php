<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class AccessingNonExistentRecordException extends ExceptionTemplate
{
    public function __construct(int $id, string $resource = "?", ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "accessing non-existent record with id: {$id} from resource: {'$resource'}",
            400,
            Severity::WARNING,
            'ACCESSING_NON_EXISTENT_RECORD',
            $previous,
            $line);
    }
}