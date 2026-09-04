<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class AccessingNonExistentResourceException extends ExceptionTemplate
{
    public function __construct(int $id, string $resource = "?", ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "accessing non-existent resource with id: {$id} from collection: {'$resource'}",
            400,
            Severity::WARNING,
            'ACCESSING_NON_EXISTENT_RESOURCE',
            $previous,
            $line);
    }
}