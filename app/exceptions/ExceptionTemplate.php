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
    private string $status;

    public function __construct(
        string $message = "",
        int $code = 0,
        Severity $severity = Severity::INFO,
        string $status = 'ERROR',
        ?Throwable $previous = null,
        int $line = -1,
    )
    {
        parent::__construct($message, $code, $previous);

        $this->severity = $severity;
        $this->line = $line;
        $this->status = $status;
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
                'error' => [
                'code' => $this->getCode(),
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'timestamp' => new DateTimeImmutable('now', new DateTimeZone('Asia/Tehran'))->format(DATE_ATOM)
            ]
            ]
        );
    }

    private function getStatus(): string
    {
        return $this->status;
    }
}
