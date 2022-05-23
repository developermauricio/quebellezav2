<?php

abstract class AbstractObjectDriver implements IObjectDriver
{
    /**
     * Returns general error code.
     *
     * @param $code
     * @return mixed
     */
    public function getErrorCode($code): int
    {
        return (int) $code;
    } // end getErrorCode

    public function createSelectQuery(
        array $columns,
        string $from,
        ?array $joins = null,
        ?array $where = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $groupBy = null,
        ?array $having = null
    ): string
    {
        $sql = "SELECT ";

        $queryColumns = array();
        foreach ($columns as $columnAlias => $columnName) {
            if ($columnName == $columnAlias) {
                $columnAlias = null;
            }
            $columnName = $this->quoteColumnName($columnName);
            $queryColumns[] = $columnAlias ? $columnName.' AS '.$columnAlias : $columnName;
        }

        $sql .= join(", ", $queryColumns)." FROM ".$from;

        if ($joins) {
            $sql .= " ".join(" ", $joins);
        }

        if ($where) {
            $sql .= " WHERE ".join(" AND ", $where);
        }

        if ($groupBy) {
            $sql .= " GROUP BY ".join(", ", $groupBy);
        }

        if ($having) {
            $sql .= " HAVING ".join(" AND ", $having);
        }

        if ($orderBy) {
            $sql .= " ORDER BY ".join(", ", $orderBy);
        }

        if ($limit) {
            $sql .= " LIMIT ".$limit;
            if ($offset) {
                $sql .= " OFFSET ".$offset;
            }
        }

        return $sql;
    } // end createSelectQuery

    public function getSplitOnPages(IDataAccessObject $object, string $query, int $col, int $page): array
    {
        throw new DatabaseException("Undefined method");
    }

}