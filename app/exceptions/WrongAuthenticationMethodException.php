<?php

namespace ghosty\taskmgr\exceptions;

use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\logger\Severity;
use Throwable;

class WrongAuthenticationMethodException extends ExceptionTemplate
{
    public function __construct(?Throwable $previous = null, int $line = -1)
    {
        parent::__construct(
            'Wrong authentication method provided. Expecting Bearer ...',
            401,
            Severity::WARNING,
            'WRONG_AUTHENTICATION_METHOD_EXCEPTION',
            $previous,
            $line
        );
    }

}