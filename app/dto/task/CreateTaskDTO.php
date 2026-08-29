<?php

namespace ghosty\taskmgr\dto\task;

use DateTimeImmutable;
use Exception;
use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\DTO;

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
            // TODO: THRoW exception
        }

        if (!array_key_exists('desc', $data)) {
            // TODO: THRoW exception
        }

        if (!array_key_exists('priority', $data)) {
            // TODO: THRoW exception
        }

        if (!array_key_exists('deadline', $data)) {
            // TODO: THRoW exception
        }

        if (!array_key_exists('status', $data)) {
            // TODO: THRoW exception
        }

        try {
            $deadline = new DateTimeImmutable($data['deadline']);
        } catch (\DateMalformedStringException $e) {
            // TODO: HANDLE THIS (IDK HOW YET)
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