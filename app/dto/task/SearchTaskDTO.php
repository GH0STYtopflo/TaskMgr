<?php

namespace ghosty\taskmgr\dto\task;

use DateTimeImmutable;
use DateTimeZone;
use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MalformedDateException;
use ghosty\taskmgr\exceptions\WrongPaginationParamsException;

class SearchTaskDTO extends DTO
{
    private string $title;
    private int $priority_higher;
    private int $priority_lower;
    private string $deadline_before;
    private string $deadline_after;
    private TaskStatus $status;
    private string $created_before;
    private string $created_after;
    private string $updated_before;
    private string $updated_after;
    private int $page;
    private int $limit;

    public function __construct(
        string     $title,
        int        $priority_higher,
        int        $priority_lower,
        string     $deadline_before,
        string     $deadline_after,
        TaskStatus $status,
        string     $created_before,
        string     $created_after,
        string     $updated_before,
        string     $updated_after,
        int        $page,
        int        $limit
    )
    {
        $this->title = $title;
        $this->priority_higher = $priority_higher;
        $this->priority_lower = $priority_lower;
        $this->deadline_before = $deadline_before;
        $this->deadline_after = $deadline_after;
        $this->status = $status;
        $this->created_before = $created_before;
        $this->created_after = $created_after;
        $this->updated_before = $updated_before;
        $this->updated_after = $updated_after;
        $this->page = $page;
        $this->limit = $limit;
    }


    public static function fromArray(array $data): self
    {
        $title = $data['title'] ?? null;
        $priority_higher = $data['priority_higher'] ?? null;
        $priority_lower = $data['priority_lower'] ?? null;
        $deadline_before = $data['deadline_before'] ?? null;
        $deadline_after = $data['deadline_after'] ?? null;
        $status = $data['status'] ?? null;
        $created_before = $data['created_before'] ?? null;
        $created_after = $data['created_after'] ?? null;
        $updated_before = $data['updated_before'] ?? null;
        $updated_after = $data['updated_after'] ?? null;

        if (isset($data['page']) && $data['page'] < 1) {
            throw new WrongPaginationParamsException(line: __LINE__);
        }

        if (isset($data['limit']) && $data['limit'] < 1) {
            throw new WrongPaginationParamsException(line: __LINE__);
        }

        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        $deadline_before = self::createDateTimeImmutable($deadline_before);
        $deadline_after = self::createDateTimeImmutable($deadline_after);
        $updated_before = self::createDateTimeImmutable($updated_before);
        $updated_after = self::createDateTimeImmutable($updated_after);
        $created_before = self::createDateTimeImmutable($created_before);
        $created_after = self::createDateTimeImmutable($created_after);

        return new self(
            $title,
            $priority_higher,
            $priority_lower,
            $deadline_before,
            $deadline_after,
            $status,
            $created_before,
            $created_after,
            $updated_before,
            $updated_after,
            $page,
            $limit
        );
    }

    private static function createDateTimeImmutable(?string $date): ?DateTimeImmutable
    {
        if (is_null($date) || $date === '') {
            return null;
        }

        if (str_contains($date, '+')) {
            throw new MalformedDateException($date, line: __LINE__);
        }

        try {
            return new DateTimeImmutable($date, new DateTimeZone('Asia/Tehran'));
        } catch (\DateMalformedStringException $e) {
            throw new MalformedDateException($date, line: __LINE__);
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPriorityHigher(): int
    {
        return $this->priority_higher;
    }

    public function getPriorityLower(): int
    {
        return $this->priority_lower;
    }

    public function getDeadlineBefore(): string
    {
        return $this->deadline_before;
    }

    public function getDeadlineAfter(): string
    {
        return $this->deadline_after;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function getCreatedBefore(): string
    {
        return $this->created_before;
    }

    public function getCreatedAfter(): string
    {
        return $this->created_after;
    }

    public function getUpdatedBefore(): string
    {
        return $this->updated_before;
    }

    public function getUpdatedAfter(): string
    {
        return $this->updated_after;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}