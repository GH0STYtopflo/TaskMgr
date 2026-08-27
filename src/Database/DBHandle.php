<?php

namespace Gh0stytopflo\Taskmgr\Database;

use PDO;
use PDOException;
use PDOStatement;

class DBHandle
{
    private PDO $connection;

    /**
     * @param PDO $connection
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Executes an SQL query and returns the resulting statement.
     *
     * Suitable for SELECT statements.
     *
     * @param string $sql The SQL query to execute.
     * @return PDOStatement The executed statement containing the result set.
     */
    public function query(string $sql): PDOStatement
    {
        $pStatement = $this->connection->query($sql);

        if (!$pStatement) {
            // TODO: LOG THIS EXCEPTION
            throw new PDOException('Query failed: ' . $sql);
        } else return $pStatement;
    }

    /**
     * Executes a prepared SQL statement with the provided values.
     *
     * Suitable for INSERT, UPDATE, and DELETE statements.
     *
     * @param string $template The SQL statement template with placeholders.
     * @param array $values The values which we'll use to bind to the placeholders.
     * @return PDOStatement The executed statement.
     */
    public function preparedStatement(string $template, array $values): PDOStatement
    {
        $pStatement = $this->connection->prepare($template);

        if (!$pStatement) {
            // TODO: LOG THIS
            throw new PDOException('PreparedStatement failed: ' . $template);
        }

        if (!$pStatement->execute($values)) {
            // TODO: LOG THIS
            throw new PDOException('PreparedStatement failed: ' . $template);
        }

        return $pStatement;
    }

    /**
     * Executes an SQL statement that does not return a result set.
     *
     * Returns the number of affected rows. Suitable for INSERT, UPDATE, DELETE,
     * and database management statements.
     *
     * @param string $sql The SQL statement to execute.
     * @return int The number of affected rows.
     */
    public function exec(string $sql): int
    {
        $effected = $this->connection->exec($sql);

        if (!$effected) {
            // TODO: LOG THIS
            throw new PDOException('Exec failed: ' . $sql);
        } else return $effected;
    }
}