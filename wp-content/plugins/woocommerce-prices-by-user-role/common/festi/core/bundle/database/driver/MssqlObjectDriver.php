<?php

class MssqlObjectDriver extends AbstractObjectDriver
{

    /**
     * @override
     */
    public function getErrorCode($code): int
    {
        $mapper = array(
            23000 => DatabaseException::ERROR_DUPLICATE
        );

        return array_key_exists($code, $mapper) ? $mapper[$code] : (int) $code;
    } // end getErrorCode

    /**
     * @override
     * @param array $columns
     * @param string $from
     * @param array|null $joins
     * @param array|null $where
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @param array|null $groupBy
     * @param array|null $having
     * @return string
     */
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
        $queryColumns = array();
        foreach ($columns as $columnAlias => $columnName) {
            if ($columnName == $columnAlias) {
                $columnAlias = null;
            }
            $columnName = $this->quoteColumnName($columnName);
            $queryColumns[] = $columnAlias ? $columnName.' AS '.$columnAlias : $columnName;
        }

        $columnsSection = join(', ', $queryColumns);
        $fromSection    = $from.' '.join(' ', $joins);

        $sql = "SELECT ".$columnsSection;

        if ($limit) {
            $orderBySection = $orderBy ? join(", ", $orderBy):array_shift($columns);

            $sql .= ", ROW_NUMBER() OVER (ORDER BY ".$orderBySection.") AS __row_num ";
        }

        $sql .= " FROM ".$fromSection;

        if ($where) {
            $sql .= " WHERE ".join(" AND ", $where);
        }

        if ($groupBy) {
            $sql .= " GROUP BY ".join(", ", $groupBy);
        }

        if ($having) {
            $sql .= " HAVING ".join(" AND ", $having);
        }

        if ($orderBy && !$limit) {
            $sql .= " ORDER BY ".join(", ", $orderBy);
        }

        if ($limit) {
            if (!$offset) $offset = 0;
            $sql = "SELECT ".
                "t.* ".
                "FROM ".
                "(".$sql.") AS t ".
                "WHERE t.__row_num BETWEEN ".$offset." AND ".$limit.
                " ORDER BY ".
                "t.__row_num";
        }

        return $sql;
    } // end createSelectQuery

    public function quoteTableName($name)
    {
        return '['.$name.']';
    } // end quoteTableName
    
    public function quoteColumnName($key)
    {
        // FIXME:
        $reserved = array('sum', 'avg', 'count');
        $regExp = "#".join("\(|", $reserved)."\(#Umis";
        if (preg_match($regExp, $key)) {
            return $key;
        }

        $key = "[".$key."]";
        if (strpos($key, '.') !== false) {
            $key = str_replace(".", "].[", $key);
        }
        
        return $key;
    } // end quoteColumnName

    // XXX: Dirty hack for SQL Server
    public function getSplitOnPages(IDataAccessObject $object, string $query, int $col, int $page): array
    {
        $orderBy = null;
        $result = array(
            'cnt' => 0
        );

        if ($page !== 0) {
            $page -= 1;
        }

        $startLimit = ($page * $col) + 1;
        $endLimit = ($page * $col) + $col;

        if (!preg_match("#ORDER BY(?<order_by>.*$)#Umis", $query, $match)) {
            throw new DatabaseException("ORDER BY statement is required for getSplitOnPages");
        }

        $orderBy = $match['order_by'];
        $injectionStatement = ", ROW_NUMBER() OVER (ORDER BY ".$orderBy.") AS __row_num, ".
                              "COUNT(*) OVER() AS __total FROM ";
        $query = preg_replace("#ORDER BY(.*$)#Umis", "", $query);
        $query = preg_replace("#FROM#Umis", $injectionStatement, $query);

        $sqlWrapper = "SELECT ".
                "t.* ".
            "FROM ".
                "(".$query.") AS t ".
            "WHERE ".
                "t.__row_num BETWEEN ".$startLimit." AND ".$endLimit." ".
            "ORDER BY ".
                "t.__row_num";

        $result['rows'] = $object->getAll($sqlWrapper);

        foreach ($result['rows'] as &$row) {
            $result['cnt'] = $row['__total'];
            unset($row['__row_num'], $row['__total']);
        }

        $result['pageCnt'] = $result['cnt'] > 0 ? ceil($result['cnt'] / $col) : 0;

        return $result;
    }// end getSplitOnPages

    public function getTableIndexes(IDataAccessObject $object, string $tableName): array
    {
        $sql = "SELECT
                    a.name AS Index_Name,
                    OBJECT_NAME(a.object_id) as table_name,
                    COL_NAME(b.object_id,b.column_id) AS Column_Name,
                    b.index_column_id,
                    b.key_ordinal,
                    b.is_included_column
                FROM
                     sys.indexes AS a
                    INNER JOIN
                     sys.index_columns AS b
                           ON a.object_id = b.object_id AND a.index_id = b.index_id
                    WHERE
                            a.is_hypothetical = 0 AND
                     a.object_id = OBJECT_ID('".$tableName."')";

        return $object->getAll($sql);
    }
}