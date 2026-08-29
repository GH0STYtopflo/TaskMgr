<?php

namespace ghosty\taskmgr\dto\task;

use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class UpdateTaskStatusDTO extends DTO
{
    private int $id;
    private TaskStatus $status;

    /**
     * @param int $id
     * @param TaskStatus $status
     */
    public function __construct(int $id, TaskStatus $status)
    {
        $this->id = $id;
        $this->status = $status;
    }


    public static function fromArray(array $data): DTO
    {
        try {
            $status = TaskStatus::from($data['status']);
        } catch (\ValueError $e) {
            throw new TypeMismatchException(
                'status',
                "string(" . $data['status'] . ")",
                'TaskStatus',
                $e,
                __LINE__
            );
        }

        if (!is_numeric($data['id'])) {
            throw new TypeMismatchException(
                'id',
                "string(" . $data['status'] . ")",
                'int',
                line: __LINE__
            );
        }

        return new self($data['id'] , $status);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['status'] = $array['status']->getValue();

        return $array;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }
}