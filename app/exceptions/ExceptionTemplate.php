<?php

namespace ghosty\taskmgr\exceptions;
use DateTimeImmutable;
use DateTimeZone;
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
        return Response::makeResponse(
            $this->getCode(),
            [Headers::TYPE_JSON],
            [
                'error' => $this->getMessage(),
                'code' => $this->getCode(),
                'timestamp' => new DateTimeImmutable('now', new DateTimeZone('Asia/Tehran'))->format(DATE_ATOM)
            ]
        );
    }
}
