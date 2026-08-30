<?php

namespace ghosty\taskmgr\dto\comment;

use DateTimeImmutable;
use DateTimeZone;
use ghosty\taskmgr\dto\DTO;

class UserCommentDTO extends DTO
{
    private int $id;
    private string $body;
    private DateTimeImmutable $submission_time;
    private int $task_id;

    /**
     * @param int $id
     * @param string $body
     * @param DateTimeImmutable $submission_time
     * @param int $task_id
     */
    public function __construct(int $id, string $body, DateTimeImmutable $submission_time, int $task_id)
    {
        $this->id = $id;
        $this->body = $body;
        $this->submission_time = $submission_time;
        $this->task_id = $task_id;
    }


    public static function fromArray(array $data): DTO
    {
        return new self(
            (int) $data['id'],
            $data['body'],
            new DateTimeImmutable($data['submission_time']),
            (int) $data['task_title']
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getSubmissionTime(): DateTimeImmutable
    {
        return $this->submission_time;
    }

    public function getTaskTitle(): string
    {
        return $this->task_title;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }
}