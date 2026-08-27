<?php

namespace Gh0stytopflo\Taskmgr\Database;
use Gh0stytopflo\Taskmgr\Exception\DatabaseException;
use Gh0stytopflo\Taskmgr\Logger\Severity;
use PDO;
use PDOException;

class Connection
{
    public static function getConnection(DBConfig $config): PDO | null
    {
        try {
            return new PDO(
                $config->getDbms() . ':host=' . $config->getHost() . ';port=' . $config->getPort() . ';dbname=' . $config->getDbname(),
                $config->getUsername(),
                $config->getPassword(),
                $config->getOptions()
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), Severity::ERROR, $e, __LINE__);
        }
    }
}
