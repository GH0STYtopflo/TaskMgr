<?php

namespace ghosty\taskmgr\dto\task;

use DateTimeImmutable;
use Exception;
use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MalformedDateException;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\logger\Severity;

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

    public static function fromArray(array $data): DTO
    {
        if (!array_key_exists('title', $data)) {
            throw new MissingParamException('title', line: __LINE__);
        }

        if (!array_key_exists('desc', $data)) {
            throw new MissingParamException('desc', line: __LINE__);
        }

        if (!array_key_exists('priority', $data)) {
            throw new MissingParamException('priority', line: __LINE__);
        }

        if (!array_key_exists('deadline', $data)) {
            throw new MissingParamException('deadline', line: __LINE__);
        }

        if (!array_key_exists('status', $data)) {
            throw new MissingParamException('status', line: __LINE__);
        }

        try {
            $deadline = new DateTimeImmutable($data['deadline']);
        } catch (\DateMalformedStringException $e) {
            throw new MalformedDateException($data['deadline'], line: __LINE__);
        }

        return new self($data['title'], $data['desc'], $data['priority'], $deadline);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['deadline'] = $this->deadline->format(DATE_ATOM);

        return $array;
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