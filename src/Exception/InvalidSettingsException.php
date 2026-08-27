<?php

namespace Gh0stytopflo\Taskmgr\Exception;

use Gh0stytopflo\Taskmgr\Logger\Severity;
use Throwable;

class InvalidSettingsException extends ExceptionTemplate
{
    public function __construct(string $option, int $code = 0, ?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            "Invalid configuration option '{$option}'",
            $code,
            Severity::ERROR,
            $previous,
            $line
        );
    }
}