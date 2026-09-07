<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\log\LogDTO;
use ghosty\taskmgr\dto\log\SearchLogDTO;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\logger\Severity;

class LogModel
{
    private DBHandle $handle;

    public function __construct(DBHandle $handle)
    {
        $this->handle = $handle;
    }

    public function log(LogDTO $data): void
    {
        $data = $data->toArray();
        $data['timestamp'] = $data['timestamp']->format(DATE_ATOM);
        $data['resource_type'] = $data['resource_type']->value;
        $data['result'] = $data['result']->value;

        try {
            $this->handle->preparedStatement(
                "INSERT INTO action_logs (resource_id, resource_type, description, result, timestamp)
                    VALUES (:resource_id, :resource_type, :description, :result, :timestamp)",
                $data);
        } catch (\PDOException) {}
    }

    public function searchLogs(SearchLogDTO $data): array
    {
        $data = $data->toArray();
        $data['before'] = $data['before']->format(DATE_ATOM);
        $data['after'] = $data['after']->format(DATE_ATOM);
        $data['resource_type'] = $data['resource_type']->value;
        $data['action_status'] = $data['action_status']->value;

        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM action_logs WHERE 
                              (:resource_type = resource_type OR :resource_type IS NULL) AND
                              (:user_id = user_id OR :user_id IS NULL) AND
                              (:resource_id = resource_id OR :resource_id IS NULL) AND
                              (action_status = :action_status OR :action_status IS NULL) AND
                              (timestamp <= :before OR :before IS NULL) AND
                              (timestamp >= :after OR :after IS NULL)",
                $data
            )->fetchAll();
        } catch (\PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                __LINE__
            );
        }
    }

}