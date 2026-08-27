<?php

namespace Gh0stytopflo\Taskmgr\Database;
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
            // TODO: LOG THIS EXCEPTION
            echo 'Connection error: ' . $e->getMessage();
            return null;
        }
    }
}
