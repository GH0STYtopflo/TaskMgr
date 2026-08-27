<?php

namespace Gh0stytopflo\Taskmgr\Database;

use Gh0stytopflo\Taskmgr\Exception\DatabaseException;
use Gh0stytopflo\Taskmgr\Logger\Severity;
use Gh0stytopflo\Taskmgr\Util\TextFormatter;
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
            throw new DatabaseException(
                "SQL query failed: {$sql}",
                severity: Severity::WARNING,
                line: __LINE__
            );
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
            throw new DatabaseException(
                "SQL prepared statement failed: {$template}",
                severity: Severity::WARNING,
                line: __LINE__
            );
        }

        if (!$pStatement->execute($values)) {
            throw new DatabaseException(
                "SQL prepared statement execution failed: $template" . " <- " . TextFormatter::assocImplode($values),
                severity: Severity::WARNING,
                line: __LINE__
            );
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
            throw new DatabaseException(
                "Exec failed: {$sql}",
                severity: Severity::WARNING,
                line: __LINE__
            );
        } else return $effected;
    }
}