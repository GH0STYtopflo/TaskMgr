<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\logger\Severity;
use Throwable;

class TypeMismatchException extends ExceptionTemplate
{
    public function __construct(string $param,string $received, string $expected, ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Expected type {$expected }, received type {$received} for param {$param}",
            400,
            Severity::WARNING,
            'PARAMETER_TYPE_MISMATCH',
            $previous,
            $line
        );
    }
}