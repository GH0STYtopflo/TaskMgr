<?php

namespace ghosty\taskmgr\exceptions;
use DateTimeImmutable;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\logger\Severity;
use ghosty\taskmgr\util\HTTP\Headers;
use RuntimeException;
use Throwable;

abstract class ExceptionTemplate extends RuntimeException
{
    private Severity $severity;

    public function __construct(
        string $message = "",
        int $code = 0,
        Severity $severity = Severity::INFO,
        ?Throwable $previous = null,
        int $line = -1
    )
    {
        parent::__construct($message, $code, $previous);

        $this->severity = $severity;
        $this->line = $line;
    }

    public function getSeverity(): Severity
    {
        return $this->severity;
    }

    public function createErrResponse(): Response
    {
        return new Response([Headers::TYPE_JSON], $this->code,
            json_encode(
                ['error' => $this->message,
                'code' => $this->code,
                'datetime' => new DateTimeImmutable(timezone: 'Asia/Tehran')->format(DATE_ATOM)]) // returns now() implicitly
        );
    }
}
