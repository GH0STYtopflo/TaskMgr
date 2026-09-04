<?php

namespace ghosty\taskmgr\dto\task;

use DateTimeImmutable;
use DateTimeZone;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MalformedDateException;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\PriorityOutOfRangeException;

class CreateTaskDTO extends DTO
{
    private string $title;
    private string $desc;
    private int $priority;
    private DateTimeImmutable $deadline;

    /**
     * @param string $title
     * @param string $desc
     * @param int $priority
     * @param DateTimeImmutable $deadline
     */
    public function __construct(string $title, string $desc, int $priority, DateTimeImmutable $deadline)
    {
        $this->title = $title;
        $this->desc = $desc;
        $this->priority = $priority;
        $this->deadline = $deadline;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['title'])) {
            throw new MissingParamException('title', line: __LINE__);
        }

        if (!isset($data['desc'])) {
            throw new MissingParamException('desc', line: __LINE__);
        }

        if (!isset($data['priority'])) {
            throw new MissingParamException('priority', line: __LINE__);
        }

        if (!isset($data['deadline'])) {
            throw new MissingParamException('deadline', line: __LINE__);
        }

        if (str_contains($data['deadline'], '+')) {
            throw new MalformedDateException($data['deadline'], line: __LINE__);
        }

        if ($data['priority'] < 1 || $data['priority'] > 20) {
            throw new PriorityOutOfRangeException($data['priority'], line: __LINE__);
        }

        try {
            $deadline = new DateTimeImmutable($data['deadline'], new DateTimeZone('Asia/Tehran'));
        } catch (\DateMalformedStringException $e) {
            throw new MalformedDateException($data['deadline'], line: __LINE__);
        }

        return new self($data['title'], $data['desc'], $data['priority'], $deadline);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDesc(): string
    {
        return $this->desc;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getDeadline(): DateTimeImmutable
    {
        return $this->deadline;
    }


}