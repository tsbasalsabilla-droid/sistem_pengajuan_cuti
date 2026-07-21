<?php

declare(strict_types=1);



namespace CodeIgniter\Database;


class TableName
{
    
    protected function __construct(
        private  string $actualTable,
        private  string $logicalTable = '',
        private  string $schema = '',
        private  string $database = '',
        private  string $alias = '',
    ) {
    }

    
    public static function create(string $dbPrefix, string $table, string $alias = ''): self
    {
        return new self(
            $dbPrefix . $table,
            $table,
            '',
            '',
            $alias,
        );
    }

    
    public static function fromActualName(string $dbPrefix, string $actualTable, string $alias = ''): self
    {
        $prefix       = $dbPrefix;
        $logicalTable = '';

        if (str_starts_with($actualTable, $prefix)) {
            $logicalTable = substr($actualTable, strlen($prefix));
        }

        return new self(
            $actualTable,
            $logicalTable,
            '',
            $alias,
        );
    }

    
    public static function fromFullName(
        string $dbPrefix,
        string $table,
        string $schema = '',
        string $database = '',
        string $alias = '',
    ): self {
        return new self(
            $dbPrefix . $table,
            $table,
            $schema,
            $database,
            $alias,
        );
    }

    
    public function getTableName(): string
    {
        return $this->logicalTable;
    }

    
    public function getActualTableName(): string
    {
        return $this->actualTable;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }
}
