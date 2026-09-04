<?php

namespace ghosty\taskmgr\dto\task;

use DateTimeImmutable;
use DateTimeZone;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MalformedDateException;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class UpdateTaskDTO extends DTO
{
    private int $id;
    private ?string $title;
    private ?string $desc;
    private ?int $priority;

    private DateTimeImmutable $deadline;

    /**
     * @param int $id
     * @param string|null $title
     * @param string|null $desc
     * @param int|null $priority
     * @param DateTimeImmutable|null $deadline
     */
    public function __construct(int $id, ?string $title, ?string $desc, ?int $priority, ?DateTimeImmutable $deadline)
    {
        $this->id = $id;
        $this->title = $title;
        $this->desc = $desc;
        $this->priority = $priority;
        $this->deadline = $deadline;
    }


    public static function fromArray(array $data): self
    {
        if (!isset($data["id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!is_numeric($data["id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

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

        if (!is_null($data['priority']) && !is_numeric($data['priority'])) {
            throw new TypeMismatchException('priority', "string(" . $data['priority'] . ")", 'int', line: __LINE__);
        }

        if (str_contains($data['deadline'], '+')) {
            throw new MalformedDateException($data['deadline'], line: __LINE__);
        }

        try {
            $deadline = new DateTimeImmutable($data['deadline'], new DateTimeZone('Asia/Tehran'));
        } catch (\DateMalformedStringException $e) {
            throw new MalformedDateException($data['deadline'], line: __LINE__);
        }

        return new self((int) $data['id'], $data['title'], $data['desc'], $data['priority'], $deadline);
    }
    public function getId(): int
    {
        return $this->id;
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