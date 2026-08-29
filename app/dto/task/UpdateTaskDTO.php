<?php

namespace ghosty\taskmgr\dto\task;

use DateTimeImmutable;
use ghosty\taskmgr\dto\DTO;

class UpdateTaskDTO extends DTO
{
    private ?string $title;
    private ?string $desc;
    private ?int $priority;

    private DateTimeImmutable $deadline;

    /**
     * @param string|null $title
     * @param string|null $desc
     * @param int|null $priority
     * @param DateTimeImmutable|null $deadline
     */
    public function __construct(?string $title, ?string $desc, ?int $priority, ?DateTimeImmutable $deadline)
    {
        $this->title = $title;
        $this->desc = $desc;
        $this->priority = $priority;
        $this->deadline = $deadline;
    }


    public static function fromArray(array $data): DTO
    {
        if (!isset($data['title'])) {
            $data['title'] = null;
        }

        if (!isset($data['desc'])) {
            $data['desc'] = null;
        }

        if (!isset($data['priority'])) {
            $data['priority'] = null;
        }

        if (!isset($data['deadline'])) {
            $data['deadline'] = null;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getDeadline(): DateTimeImmutable
    {
        return $this->deadline;
    }
}