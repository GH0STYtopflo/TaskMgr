<?php

namespace ghosty\taskmgr\dto\task;

use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\DTO;

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
        } catch (\Exception $e) {
            // TODO: THROW BETTER EXCEPTION
        }

        if (!is_numeric($data['id'])) {
            // TODO: THROW EXCEPTION
        }

        return new self($data['id'] , $status);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['status'] = $array['status']->getValue();

        return $array;
    }
}