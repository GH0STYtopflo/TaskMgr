<?php

namespace ghosty\taskmgr\database;

use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\logger\Severity;
use PDO;
use PDOException;

class Connection
{
    public static function getConnection(
        string $dbms,
        string $host,
        string $port,
        string $dbname,
        string $dbuser,
        string $dbpass,
        array  $options
    ): PDO
    {
        try {
            return new PDO(
                $dbms . ':host=' . $host . ';port=' . $port . ';dbname=' . $dbname,
                $dbuser,
                $dbpass,
                $options
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), Severity::ERROR, $e, __LINE__);
        }
    }
}
