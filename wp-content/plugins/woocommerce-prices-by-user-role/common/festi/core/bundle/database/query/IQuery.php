<?php

namespace core\dao;

interface IQuery
{
    public function column(string $name, string $alias = null): IQuery;
    public function from(string $name): IQuery;
    public function where(array $search): IQuery;
    public function groupBy(string $columnName): IQuery;
    public function orderBy(string $columnName): IQuery;
    public function limit(int $limit, int $offset = null): IQuery;
    public function join(string $join): IQuery;
    public function joins(array $joins): IQuery;
}