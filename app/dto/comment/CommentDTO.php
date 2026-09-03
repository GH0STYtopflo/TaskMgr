<?php

namespace ghosty\taskmgr\dto\comment;

use DateTimeImmutable;
use ghosty\taskmgr\dto\DTO;

class CommentDTO extends DTO
{
    private int $id;
    private string $body;
    private DateTimeImmutable $submission_time;
    private int $user_id;
    private int $task_id;

    /**
     * @param int $id
     * @param string $body
     * @param DateTimeImmutable $submission_time
     * @param int $user_id
     * @param int $task_id
     */
    public function __construct(int $id, string $body, DateTimeImmutable $submission_time, int $user_id, int $task_id)
    {
        $this->id = $id;
        $this->body = $body;
        $this->submission_time = $submission_time;
        $this->user_id = $user_id;
        $this->task_id = $task_id;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            $data['body'],
            new DateTimeImmutable($data['submission_time']),
            (int) $data['user_id'],
            (int) $data['task_id'],
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

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }
}