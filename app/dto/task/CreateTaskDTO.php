<?php

namespace ghosty\taskmgr\dto\task;

use DateTimeImmutable;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\util\datetime\DateTimeHelper;

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

        if (!isset($data['status'])) {
            throw new MissingParamException('status', line: __LINE__);
        }

        $deadline = DateTimeHelper::fromString($data['deadline']);

        return new self($data['title'], $data['desc'], $data['priority'], $deadline);
    }

    public function toArray(): array
    {
        $array =  parent::toArray();
        $array['deadline'] = DateTimeHelper::toString($array['deadline']);

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