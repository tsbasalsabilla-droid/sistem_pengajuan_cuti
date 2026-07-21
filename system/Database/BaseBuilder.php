<?php

declare(strict_types=1);



namespace CodeIgniter\Database;

use Closure;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Traits\ConditionalTrait;
use Config\Feature;


class BaseBuilder
{
    use ConditionalTrait;

    
    protected $resetDeleteData = false;

    
    protected $QBSelect = [];

    
    protected $QBDistinct = false;

    
    protected $QBFrom = [];

    
    protected $QBJoin = [];

    
    protected $QBWhere = [];

    
    public $QBGroupBy = [];

    
    protected $QBHaving = [];

    
    protected $QBKeys = [];

    
    protected $QBLimit = false;

    
    protected $QBOffset = false;

    
    public $QBOrderBy = [];

    
    protected array $QBUnion = [];

    
    public $QBNoEscape = [];

    
    protected $QBSet = [];

    
    protected $QBWhereGroupStarted = false;

    
    protected $QBWhereGroupCount = 0;

    
    protected $QBIgnore = false;

    
    protected $QBOptions;

    
    protected $db;

    
    protected $tableName;

    
    protected $randomKeyword = [
        'RAND()',
        'RAND(%d)',
    ];

    
    protected $countString = 'SELECT COUNT(*) AS ';

    
    protected $binds = [];

    
    protected $bindsKeyCount = [];

    
    protected $canLimitDeletes = true;

    
    protected $canLimitWhereUpdates = true;

    
    protected $supportedIgnoreStatements = [];

    
    protected $testMode = false;

    
    protected $joinTypes = [
        'LEFT',
        'RIGHT',
        'OUTER',
        'INNER',
        'LEFT OUTER',
        'RIGHT OUTER',
    ];

    
    protected $isLiteralStr = [];

    
    protected $pregOperators = [];

    
    public function __construct($tableName, ConnectionInterface $db, ?array $options = null)
    {
        if (empty($tableName)) {
            throw new DatabaseException('A table must be specified when creating a new Query Builder.');
        }

        
        $this->db = $db;

        if ($tableName instanceof TableName) {
            $this->tableName = $tableName->getTableName();
            $this->QBFrom[]  = $this->db->escapeIdentifier($tableName);
            $this->db->addTableAlias($tableName->getAlias());
        }
        
        elseif (is_string($tableName) && ! str_contains($tableName, ',')) {
            $this->tableName = $tableName;  
            $this->from($tableName);
        } else {
            $this->tableName = '';
            $this->from($tableName);
        }

        if ($options !== null && $options !== []) {
            foreach ($options as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    
    public function db(): ConnectionInterface
    {
        return $this->db;
    }

    
    public function testMode(bool $mode = true)
    {
        $this->testMode = $mode;

        return $this;
    }

    
    public function getTable(): string
    {
        return $this->tableName;
    }

    
    public function getBinds(): array
    {
        return $this->binds;
    }

    
    public function ignore(bool $ignore = true)
    {
        $this->QBIgnore = $ignore;

        return $this;
    }

    
    public function select($select = '*', ?bool $escape = null)
    {
        
        if (! is_bool($escape)) {
            $escape = $this->db->protectIdentifiers;
        }

        if ($select instanceof RawSql) {
            $select = [$select];
        }

        if (is_string($select)) {
            $select = ($escape === false) ? [$select] : explode(',', $select);
        }

        foreach ($select as $val) {
            if ($val instanceof RawSql) {
                $this->QBSelect[]   = $val;
                $this->QBNoEscape[] = false;

                continue;
            }

            $val = trim($val);

            if ($val !== '') {
                $this->QBSelect[] = $val;

                
                if (mb_stripos($val, 'NULL') === 0) {
                    $this->QBNoEscape[] = false;

                    continue;
                }

                $this->QBNoEscape[] = $escape;
            }
        }

        return $this;
    }

    
    public function selectMax(string $select = '', string $alias = '')
    {
        return $this->maxMinAvgSum($select, $alias);
    }

    
    public function selectMin(string $select = '', string $alias = '')
    {
        return $this->maxMinAvgSum($select, $alias, 'MIN');
    }

    
    public function selectAvg(string $select = '', string $alias = '')
    {
        return $this->maxMinAvgSum($select, $alias, 'AVG');
    }

    
    public function selectSum(string $select = '', string $alias = '')
    {
        return $this->maxMinAvgSum($select, $alias, 'SUM');
    }

    
    public function selectCount(string $select = '', string $alias = '')
    {
        return $this->maxMinAvgSum($select, $alias, 'COUNT');
    }

    
    public function selectSubquery(BaseBuilder $subquery, string $as): self
    {
        $this->QBSelect[] = $this->buildSubquery($subquery, true, $as);

        return $this;
    }

    
    protected function maxMinAvgSum(string $select = '', string $alias = '', string $type = 'MAX')
    {
        if ($select === '') {
            throw DataException::forEmptyInputGiven('Select');
        }

        if (str_contains($select, ',')) {
            throw DataException::forInvalidArgument('column name not separated by comma');
        }

        $type = strtoupper($type);

        if (! in_array($type, ['MAX', 'MIN', 'AVG', 'SUM', 'COUNT'], true)) {
            throw new DatabaseException('Invalid function type: ' . $type);
        }

        if ($alias === '') {
            $alias = $this->createAliasFromTable(trim($select));
        }

        $sql = $type . '(' . $this->db->protectIdentifiers(trim($select)) . ') AS ' . $this->db->escapeIdentifiers(trim($alias));

        $this->QBSelect[]   = $sql;
        $this->QBNoEscape[] = null;

        return $this;
    }

    
    protected function createAliasFromTable(string $item): string
    {
        if (str_contains($item, '.')) {
            $item = explode('.', $item);

            return end($item);
        }

        return $item;
    }

    
    public function distinct(bool $val = true)
    {
        $this->QBDistinct = $val;

        return $this;
    }

    
    public function from($from, bool $overwrite = false): self
    {
        if ($overwrite) {
            $this->QBFrom = [];
            $this->db->setAliasedTables([]);
        }

        foreach ((array) $from as $table) {
            if (str_contains($table, ',')) {
                $this->from(explode(',', $table));
            } else {
                $table = trim($table);

                if ($table === '') {
                    continue;
                }

                $this->trackAliases($table);
                $this->QBFrom[] = $this->db->protectIdentifiers($table, true, null, false);
            }
        }

        return $this;
    }

    
    public function fromSubquery(BaseBuilder $from, string $alias): self
    {
        $table = $this->buildSubquery($from, true, $alias);

        $this->db->addTableAlias($alias);
        $this->QBFrom[] = $table;

        return $this;
    }

    
    public function join(string $table, $cond, string $type = '', ?bool $escape = null)
    {
        if ($type !== '') {
            $type = strtoupper(trim($type));

            if (! in_array($type, $this->joinTypes, true)) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }

        
        
        $this->trackAliases($table);

        if (! is_bool($escape)) {
            $escape = $this->db->protectIdentifiers;
        }

        
        if ($escape === true) {
            $table = $this->db->protectIdentifiers($table, true, null, false);
        }

        if ($cond instanceof RawSql) {
            $this->QBJoin[] = $type . 'JOIN ' . $table . ' ON ' . $cond;

            return $this;
        }

        if (! $this->hasOperator($cond)) {
            $cond = ' USING (' . ($escape ? $this->db->escapeIdentifiers($cond) : $cond) . ')';
        } elseif ($escape === false) {
            $cond = ' ON ' . $cond;
        } else {
            
            
            if (preg_match_all('/\sAND\s|\sOR\s/i', $cond, $joints, PREG_OFFSET_CAPTURE) >= 1) {
                $conditions = [];
                $joints     = $joints[0];
                array_unshift($joints, ['', 0]);

                for ($i = count($joints) - 1, $pos = strlen($cond); $i >= 0; $i--) {
                    $joints[$i][1] += strlen($joints[$i][0]); 
                    $conditions[$i] = substr($cond, $joints[$i][1], $pos - $joints[$i][1]);
                    $pos            = $joints[$i][1] - strlen($joints[$i][0]);
                    $joints[$i]     = $joints[$i][0];
                }
                ksort($conditions);
            } else {
                $conditions = [$cond];
                $joints     = [''];
            }

            $cond = ' ON ';

            foreach ($conditions as $i => $condition) {
                $operator = $this->getOperator($condition);

                
                if ($operator === false) {
                    $cond .= $joints[$i] . $condition;

                    continue;
                }

                $cond .= $joints[$i];
                $cond .= preg_match('/(\(*)?([\[\]\w\.\'-]+)' . preg_quote($operator, '/') . '(.*)/i', $condition, $match) ? $match[1] . $this->db->protectIdentifiers($match[2]) . $operator . $this->db->protectIdentifiers($match[3]) : $condition;
            }
        }

        
        $this->QBJoin[] = $type . 'JOIN ' . $table . $cond;

        return $this;
    }

    
    public function where($key, $value = null, ?bool $escape = null)
    {
        return $this->whereHaving('QBWhere', $key, $value, 'AND ', $escape);
    }

    
    public function orWhere($key, $value = null, ?bool $escape = null)
    {
        return $this->whereHaving('QBWhere', $key, $value, 'OR ', $escape);
    }

    
    protected function whereHaving(string $qbKey, $key, $value = null, string $type = 'AND ', ?bool $escape = null)
    {
        $rawSqlOnly = false;

        if ($key instanceof RawSql) {
            if ($value === null) {
                $keyValue   = [(string) $key => $key];
                $rawSqlOnly = true;
            } else {
                $keyValue = [(string) $key => $value];
            }
        } elseif (! is_array($key)) {
            $keyValue = [$key => $value];
        } else {
            $keyValue = $key;
        }

        
        if (! is_bool($escape)) {
            $escape = $this->db->protectIdentifiers;
        }

        foreach ($keyValue as $k => $v) {
            $prefix = empty($this->{$qbKey}) ? $this->groupGetType('') : $this->groupGetType($type);

            if ($rawSqlOnly) {
                $k  = '';
                $op = '';
            } elseif ($v !== null) {
                $op = $this->getOperatorFromWhereKey($k);

                if (! empty($op)) {
                    $k = trim($k);

                    end($op);
                    $op = trim(current($op));

                    
                    if (str_ends_with($k, $op)) {
                        $k  = rtrim(substr($k, 0, -strlen($op)));
                        $op = " {$op}";
                    } else {
                        $op = '';
                    }
                } else {
                    $op = ' =';
                }

                if ($this->isSubquery($v)) {
                    $v = $this->buildSubquery($v, true);
                } else {
                    $bind = $this->setBind($k, $v, $escape);
                    $v    = " :{$bind}:";
                }
            } elseif (! $this->hasOperator($k) && $qbKey !== 'QBHaving') {
                
                $op = ' IS NULL';
            } elseif (
                
                preg_match(
                    '/\s*(!?=|<>|IS(?:\s+NOT)?)\s*$/i',
                    $k,
                    $match,
                    PREG_OFFSET_CAPTURE,
                )
            ) {
                $k  = substr($k, 0, $match[0][1]);
                $op = $match[1][0] === '=' ? ' IS NULL' : ' IS NOT NULL';
            } else {
                $op = '';
            }

            if ($v instanceof RawSql) {
                $this->{$qbKey}[] = [
                    'condition' => $v->with($prefix . $k . $op . $v),
                    'escape'    => $escape,
                ];
            } else {
                $this->{$qbKey}[] = [
                    'condition' => $prefix . $k . $op . $v,
                    'escape'    => $escape,
                ];
            }
        }

        return $this;
    }

    
    public function whereIn(?string $key = null, $values = null, ?bool $escape = null)
    {
        return $this->_whereIn($key, $values, false, 'AND ', $escape);
    }

    
    public function orWhereIn(?string $key = null, $values = null, ?bool $escape = null)
    {
        return $this->_whereIn($key, $values, false, 'OR ', $escape);
    }

    
    public function whereNotIn(?string $key = null, $values = null, ?bool $escape = null)
    {
        return $this->_whereIn($key, $values, true, 'AND ', $escape);
    }

    
    public function orWhereNotIn(?string $key = null, $values = null, ?bool $escape = null)
    {
        return $this->_whereIn($key, $values, true, 'OR ', $escape);
    }

    
    public function havingIn(?string $key = null, $values = null, ?bool $escape = null)
    {
        return $this->_whereIn($key, $values, false, 'AND ', $escape, 'QBHaving');
    }

    
    public function orHavingIn(?string $key = null, $values = null, ?bool $escape = null)
    {
        return $this->_whereIn($key, $values, false, 'OR ', $escape, 'QBHaving');
    }

    
    public function havingNotIn(?string $key = null, $values = null, ?bool $escape = null)
    {
        return $this->_whereIn($key, $values, true, 'AND ', $escape, 'QBHaving');
    }

    
    public function orHavingNotIn(?string $key = null, $values = null, ?bool $escape = null)
    {
        return $this->_whereIn($key, $values, true, 'OR ', $escape, 'QBHaving');
    }

    
    protected function _whereIn(?string $key = null, $values = null, bool $not = false, string $type = 'AND ', ?bool $escape = null, string $clause = 'QBWhere')
    {
        if ($key === null || $key === '') {
            throw new InvalidArgumentException(sprintf('%s() expects $key to be a non-empty string', debug_backtrace(0, 2)[1]['function']));
        }

        if ($values === null || (! is_array($values) && ! $this->isSubquery($values))) {
            throw new InvalidArgumentException(sprintf('%s() expects $values to be of type array or closure', debug_backtrace(0, 2)[1]['function']));
        }

        if (! is_bool($escape)) {
            $escape = $this->db->protectIdentifiers;
        }

        $ok = $key;

        if ($escape === true) {
            $key = $this->db->protectIdentifiers($key);
        }

        $not = ($not) ? ' NOT' : '';

        if ($this->isSubquery($values)) {
            $whereIn = $this->buildSubquery($values, true);
            $escape  = false;
        } else {
            $whereIn = array_values($values);
        }

        $ok = $this->setBind($ok, $whereIn, $escape);

        $prefix = empty($this->{$clause}) ? $this->groupGetType('') : $this->groupGetType($type);

        $whereIn = [
            'condition' => "{$prefix}{$key}{$not} IN :{$ok}:",
            'escape'    => false,
        ];

        $this->{$clause}[] = $whereIn;

        return $this;
    }

    
    public function like($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
    {
        return $this->_like($field, $match, 'AND ', $side, '', $escape, $insensitiveSearch);
    }

    
    public function notLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
    {
        return $this->_like($field, $match, 'AND ', $side, 'NOT', $escape, $insensitiveSearch);
    }

    
    public function orLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
    {
        return $this->_like($field, $match, 'OR ', $side, '', $escape, $insensitiveSearch);
    }

    
    public function orNotLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
    {
        return $this->_like($field, $match, 'OR ', $side, 'NOT', $escape, $insensitiveSearch);
    }

    
    public function havingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
    {
        return $this->_like($field, $match, 'AND ', $side, '', $escape, $insensitiveSearch, 'QBHaving');
    }

    
    public function notHavingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
    {
        return $this->_like($field, $match, 'AND ', $side, 'NOT', $escape, $insensitiveSearch, 'QBHaving');
    }

    
    public function orHavingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
    {
        return $this->_like($field, $match, 'OR ', $side, '', $escape, $insensitiveSearch, 'QBHaving');
    }

    
    public function orNotHavingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
    {
        return $this->_like($field, $match, 'OR ', $side, 'NOT', $escape, $insensitiveSearch, 'QBHaving');
    }

    
    protected function _like($field, string $match = '', string $type = 'AND ', string $side = 'both', string $not = '', ?bool $escape = null, bool $insensitiveSearch = false, string $clause = 'QBWhere')
    {
        $escape = is_bool($escape) ? $escape : $this->db->protectIdentifiers;
        $side   = strtolower($side);

        if ($field instanceof RawSql) {
            $k                 = (string) $field;
            $v                 = $match;
            $insensitiveSearch = false;

            $prefix = empty($this->{$clause}) ? $this->groupGetType('') : $this->groupGetType($type);

            if ($side === 'none') {
                $bind = $this->setBind($field->getBindingKey(), $v, $escape);
            } elseif ($side === 'before') {
                $bind = $this->setBind($field->getBindingKey(), "%{$v}", $escape);
            } elseif ($side === 'after') {
                $bind = $this->setBind($field->getBindingKey(), "{$v}%", $escape);
            } else {
                $bind = $this->setBind($field->getBindingKey(), "%{$v}%", $escape);
            }

            $likeStatement = $this->_like_statement($prefix, $k, $not, $bind, $insensitiveSearch);

            
            if ($escape === true && $this->db->likeEscapeStr !== '') {
                $likeStatement .= sprintf($this->db->likeEscapeStr, $this->db->likeEscapeChar);
            }

            $this->{$clause}[] = [
                'condition' => $field->with($likeStatement),
                'escape'    => $escape,
            ];

            return $this;
        }

        $keyValue = is_array($field) ? $field : [$field => $match];

        foreach ($keyValue as $k => $v) {
            if ($insensitiveSearch) {
                $v = mb_strtolower($v, 'UTF-8');
            }

            $prefix = empty($this->{$clause}) ? $this->groupGetType('') : $this->groupGetType($type);

            if ($side === 'none') {
                $bind = $this->setBind($k, $v, $escape);
            } elseif ($side === 'before') {
                $bind = $this->setBind($k, "%{$v}", $escape);
            } elseif ($side === 'after') {
                $bind = $this->setBind($k, "{$v}%", $escape);
            } else {
                $bind = $this->setBind($k, "%{$v}%", $escape);
            }

            $likeStatement = $this->_like_statement($prefix, $k, $not, $bind, $insensitiveSearch);

            
            if ($escape === true && $this->db->likeEscapeStr !== '') {
                $likeStatement .= sprintf($this->db->likeEscapeStr, $this->db->likeEscapeChar);
            }

            $this->{$clause}[] = [
                'condition' => $likeStatement,
                'escape'    => $escape,
            ];
        }

        return $this;
    }

    
    protected function _like_statement(?string $prefix, string $column, ?string $not, string $bind, bool $insensitiveSearch = false): string
    {
        if ($insensitiveSearch) {
            return "{$prefix} LOWER(" . $this->db->escapeIdentifiers($column) . ") {$not} LIKE :{$bind}:";
        }

        return "{$prefix} {$column} {$not} LIKE :{$bind}:";
    }

    
    public function union($union)
    {
        return $this->addUnionStatement($union);
    }

    
    public function unionAll($union)
    {
        return $this->addUnionStatement($union, true);
    }

    
    protected function addUnionStatement($union, bool $all = false)
    {
        $this->QBUnion[] = "\nUNION "
            . ($all ? 'ALL ' : '')
            . 'SELECT * FROM '
            . $this->buildSubquery($union, true, 'uwrp' . (count($this->QBUnion) + 1));

        return $this;
    }

    
    public function groupStart()
    {
        return $this->groupStartPrepare();
    }

    
    public function orGroupStart()
    {
        return $this->groupStartPrepare('', 'OR ');
    }

    
    public function notGroupStart()
    {
        return $this->groupStartPrepare('NOT ');
    }

    
    public function orNotGroupStart()
    {
        return $this->groupStartPrepare('NOT ', 'OR ');
    }

    
    public function groupEnd()
    {
        return $this->groupEndPrepare();
    }

    
    public function havingGroupStart()
    {
        return $this->groupStartPrepare('', 'AND ', 'QBHaving');
    }

    
    public function orHavingGroupStart()
    {
        return $this->groupStartPrepare('', 'OR ', 'QBHaving');
    }

    
    public function notHavingGroupStart()
    {
        return $this->groupStartPrepare('NOT ', 'AND ', 'QBHaving');
    }

    
    public function orNotHavingGroupStart()
    {
        return $this->groupStartPrepare('NOT ', 'OR ', 'QBHaving');
    }

    
    public function havingGroupEnd()
    {
        return $this->groupEndPrepare('QBHaving');
    }

    
    protected function groupStartPrepare(string $not = '', string $type = 'AND ', string $clause = 'QBWhere')
    {
        $type = $this->groupGetType($type);

        $this->QBWhereGroupStarted = true;
        $prefix                    = empty($this->{$clause}) ? '' : $type;
        $where                     = [
            'condition' => $prefix . $not . str_repeat(' ', ++$this->QBWhereGroupCount) . ' (',
            'escape'    => false,
        ];

        $this->{$clause}[] = $where;

        return $this;
    }

    
    protected function groupEndPrepare(string $clause = 'QBWhere')
    {
        $this->QBWhereGroupStarted = false;
        $where                     = [
            'condition' => str_repeat(' ', $this->QBWhereGroupCount--) . ')',
            'escape'    => false,
        ];

        $this->{$clause}[] = $where;

        return $this;
    }

    
    protected function groupGetType(string $type): string
    {
        if ($this->QBWhereGroupStarted) {
            $type                      = '';
            $this->QBWhereGroupStarted = false;
        }

        return $type;
    }

    
    public function groupBy($by, ?bool $escape = null)
    {
        if (! is_bool($escape)) {
            $escape = $this->db->protectIdentifiers;
        }

        if (is_string($by)) {
            $by = ($escape === true) ? explode(',', $by) : [$by];
        }

        foreach ($by as $val) {
            $val = trim($val);

            if ($val !== '') {
                $val = [
                    'field'  => $val,
                    'escape' => $escape,
                ];

                $this->QBGroupBy[] = $val;
            }
        }

        return $this;
    }

    
    public function having($key, $value = null, ?bool $escape = null)
    {
        return $this->whereHaving('QBHaving', $key, $value, 'AND ', $escape);
    }

    
    public function orHaving($key, $value = null, ?bool $escape = null)
    {
        return $this->whereHaving('QBHaving', $key, $value, 'OR ', $escape);
    }

    
    public function orderBy(string $orderBy, string $direction = '', ?bool $escape = null)
    {
        if ($orderBy === '') {
            return $this;
        }

        $qbOrderBy = [];

        $direction = strtoupper(trim($direction));

        if ($direction === 'RANDOM') {
            $direction = '';
            $orderBy   = ctype_digit($orderBy) ? sprintf($this->randomKeyword[1], $orderBy) : $this->randomKeyword[0];
            $escape    = false;
        } elseif ($direction !== '') {
            $direction = in_array($direction, ['ASC', 'DESC'], true) ? ' ' . $direction : '';
        }

        if ($escape === null) {
            $escape = $this->db->protectIdentifiers;
        }

        if ($escape === false) {
            $qbOrderBy[] = [
                'field'     => $orderBy,
                'direction' => $direction,
                'escape'    => false,
            ];
        } else {
            foreach (explode(',', $orderBy) as $field) {
                $qbOrderBy[] = ($direction === '' && preg_match('/\s+(ASC|DESC)$/i', rtrim($field), $match, PREG_OFFSET_CAPTURE))
                    ? [
                        'field'     => ltrim(substr($field, 0, $match[0][1])),
                        'direction' => ' ' . $match[1][0],
                        'escape'    => true,
                    ]
                    : [
                        'field'     => trim($field),
                        'direction' => $direction,
                        'escape'    => true,
                    ];
            }
        }

        $this->QBOrderBy = array_merge($this->QBOrderBy, $qbOrderBy);

        return $this;
    }

    
    public function limit(?int $value = null, ?int $offset = 0)
    {
        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll && $value === 0) {
            $value = null;
        }

        if ($value !== null) {
            $this->QBLimit = $value;
        }

        if ($offset !== null && $offset !== 0) {
            $this->QBOffset = $offset;
        }

        return $this;
    }

    
    public function offset(int $offset)
    {
        if ($offset !== 0) {
            $this->QBOffset = $offset;
        }

        return $this;
    }

    
    protected function _limit(string $sql, bool $offsetIgnore = false): string
    {
        return $sql . ' LIMIT ' . ($offsetIgnore === false && $this->QBOffset ? $this->QBOffset . ', ' : '') . $this->QBLimit;
    }

    
    public function set($key, $value = '', ?bool $escape = null)
    {
        $key = $this->objectToArray($key);

        if (! is_array($key)) {
            $key = [$key => $value];
        }

        $escape = is_bool($escape) ? $escape : $this->db->protectIdentifiers;

        foreach ($key as $k => $v) {
            if ($escape) {
                $bind = $this->setBind($k, $v, $escape);

                $this->QBSet[$this->db->protectIdentifiers($k, false)] = ":{$bind}:";
            } else {
                $this->QBSet[$this->db->protectIdentifiers($k, false)] = $v;
            }
        }

        return $this;
    }

    
    public function getSetData(bool $clean = false): array
    {
        $data = $this->QBSet;

        if ($clean) {
            $this->QBSet = [];
        }

        return $data;
    }

    
    public function getCompiledSelect(bool $reset = true): string
    {
        $select = $this->compileSelect();

        if ($reset) {
            $this->resetSelect();
        }

        return $this->compileFinalQuery($select);
    }

    
    protected function compileFinalQuery(string $sql): string
    {
        $query = new Query($this->db);
        $query->setQuery($sql, $this->binds, false);

        if (! empty($this->db->swapPre) && ! empty($this->db->DBPrefix)) {
            $query->swapPrefix($this->db->DBPrefix, $this->db->swapPre);
        }

        return $query->getQuery();
    }

    
    public function get(?int $limit = null, int $offset = 0, bool $reset = true)
    {
        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll && $limit === 0) {
            $limit = null;
        }

        if ($limit !== null) {
            $this->limit($limit, $offset);
        }

        $result = $this->testMode
            ? $this->getCompiledSelect($reset)
            : $this->db->query($this->compileSelect(), $this->binds, false);

        if ($reset) {
            $this->resetSelect();

            
            $this->binds = [];
        }

        return $result;
    }

    
    public function countAll(bool $reset = true)
    {
        $table = $this->QBFrom[0];

        $sql = $this->countString . $this->db->escapeIdentifiers('numrows') . ' FROM ' .
            $this->db->protectIdentifiers($table, true, null, false);

        if ($this->testMode) {
            return $sql;
        }

        $query = $this->db->query($sql, null, false);

        if (empty($query->getResult())) {
            return 0;
        }

        $query = $query->getRow();

        if ($reset) {
            $this->resetSelect();
        }

        return (int) $query->numrows;
    }

    
    public function countAllResults(bool $reset = true)
    {
        
        
        
        $orderBy = [];

        if (! empty($this->QBOrderBy)) {
            $orderBy = $this->QBOrderBy;

            $this->QBOrderBy = null;
        }

        
        $limit = $this->QBLimit;

        $this->QBLimit = false;

        if ($this->QBDistinct === true || ! empty($this->QBGroupBy)) {
            
            $select = $this->QBSelect;
            $sql    = $this->countString . $this->db->protectIdentifiers('numrows') . "\nFROM (\n" . $this->compileSelect() . "\n) CI_count_all_results";

            
            $this->QBSelect = $select;
            unset($select);
        } else {
            $sql = $this->compileSelect($this->countString . $this->db->protectIdentifiers('numrows'));
        }

        if ($this->testMode) {
            return $sql;
        }

        $result = $this->db->query($sql, $this->binds, false);

        if ($reset) {
            $this->resetSelect();
        } elseif (! isset($this->QBOrderBy)) {
            $this->QBOrderBy = $orderBy;
        }

        
        $this->QBLimit = $limit;

        $row = $result instanceof ResultInterface ? $result->getRow() : null;

        if (empty($row)) {
            return 0;
        }

        return (int) $row->numrows;
    }

    
    public function getCompiledQBWhere()
    {
        return $this->QBWhere;
    }

    
    public function getWhere($where = null, ?int $limit = null, ?int $offset = 0, bool $reset = true)
    {
        if ($where !== null) {
            $this->where($where);
        }

        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll && $limit === 0) {
            $limit = null;
        }

        if ($limit !== null) {
            $this->limit($limit, $offset);
        }

        $result = $this->testMode
            ? $this->getCompiledSelect($reset)
            : $this->db->query($this->compileSelect(), $this->binds, false);

        if ($reset) {
            $this->resetSelect();

            
            $this->binds = [];
        }

        return $result;
    }

    
    protected function batchExecute(string $renderMethod, int $batchSize = 100)
    {
        if (empty($this->QBSet)) {
            if ($this->db->DBDebug) {
                throw new DatabaseException(trim($renderMethod, '_') . '() has no data.');
            }

            return false; 
        }

        $table = $this->db->protectIdentifiers($this->QBFrom[0], true, null, false);

        $affectedRows = 0;
        $savedSQL     = [];
        $cnt          = count($this->QBSet);

        
        if ($batchSize === 0) {
            $batchSize = $cnt;
        }

        for ($i = 0, $total = $cnt; $i < $total; $i += $batchSize) {
            $QBSet = array_slice($this->QBSet, $i, $batchSize);

            $sql = $this->{$renderMethod}($table, $this->QBKeys, $QBSet);

            if ($sql === '') {
                return false; 
            }

            if ($this->testMode) {
                $savedSQL[] = $sql;
            } else {
                $this->db->query($sql, null, false);
                $affectedRows += $this->db->affectedRows();
            }
        }

        if (! $this->testMode) {
            $this->resetWrite();
        }

        return $this->testMode ? $savedSQL : $affectedRows;
    }

    
    public function setData($set, ?bool $escape = null, string $alias = '')
    {
        if (empty($set)) {
            if ($this->db->DBDebug) {
                throw new DatabaseException('setData() has no data.');
            }

            return null; 
        }

        $this->setAlias($alias);

        
        if (is_object($set) || (! is_array(current($set)) && ! is_object(current($set)))) {
            $set = [$set];
        }

        $set = $this->batchObjectToArray($set);

        $escape = is_bool($escape) ? $escape : $this->db->protectIdentifiers;

        $keys = array_keys($this->objectToArray(current($set)));
        sort($keys);

        foreach ($set as $row) {
            $row = $this->objectToArray($row);
            if (array_diff($keys, array_keys($row)) !== [] || array_diff(array_keys($row), $keys) !== []) {
                
                $this->QBSet[] = [];

                return null;
            }

            ksort($row); 

            $clean = [];

            foreach ($row as $rowValue) {
                $clean[] = $escape ? $this->db->escape($rowValue) : $rowValue;
            }

            $row = $clean;

            $this->QBSet[] = $row;
        }

        foreach ($keys as $k) {
            $k = $this->db->protectIdentifiers($k, false);

            if (! in_array($k, $this->QBKeys, true)) {
                $this->QBKeys[] = $k;
            }
        }

        return $this;
    }

    
    public function getCompiledUpsert()
    {
        [$currentTestMode, $this->testMode] = [$this->testMode, true];

        $sql = implode(";\n", $this->upsert());

        $this->testMode = $currentTestMode;

        return $this->compileFinalQuery($sql);
    }

    
    public function upsert($set = null, ?bool $escape = null)
    {
        
        if ($set === null && ! is_array(current($this->QBSet))) {
            $set = [];

            foreach ($this->QBSet as $field => $value) {
                $k = trim($field, $this->db->escapeChar);
                
                $set[$k] = isset($this->binds[$k]) ? $this->binds[$k][0] : new RawSql($value);
            }

            $this->binds = [];

            $this->resetRun([
                'QBSet'  => [],
                'QBKeys' => [],
            ]);

            $this->setData($set, true); 
        } elseif ($set !== null) {
            $this->setData($set, $escape);
        } 

        return $this->batchExecute('_upsertBatch');
    }

    
    public function upsertBatch($set = null, ?bool $escape = null, int $batchSize = 100)
    {
        if (isset($this->QBOptions['setQueryAsData'])) {
            $sql = $this->_upsertBatch($this->QBFrom[0], $this->QBKeys, []);

            if ($sql === '') {
                return false; 
            }

            if ($this->testMode === false) {
                $this->db->query($sql, null, false);
            }

            $this->resetWrite();

            return $this->testMode ? $sql : $this->db->affectedRows();
        }

        if ($set !== null) {
            $this->setData($set, $escape);
        }

        return $this->batchExecute('_upsertBatch', $batchSize);
    }

    
    protected function _upsertBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $updateFields = $this->QBOptions['updateFields'] ?? $this->updateFields($keys)->QBOptions['updateFields'] ?? [];

            $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $keys) . ")\n{:_table_:}ON DUPLICATE KEY UPDATE\n" . implode(
                ",\n",
                array_map(
                    static fn ($key, $value): string => $table . '.' . $key . ($value instanceof RawSql ?
                        ' = ' . $value :
                        ' = VALUES(' . $value . ')'),
                    array_keys($updateFields),
                    $updateFields,
                ),
            );

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'] . "\n";
        } else {
            $data = 'VALUES ' . implode(', ', $this->formatValues($values)) . "\n";
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    private function setAlias(string $alias): BaseBuilder
    {
        if ($alias !== '') {
            $this->db->addTableAlias($alias);
            $this->QBOptions['alias'] = $this->db->protectIdentifiers($alias);
        }

        return $this;
    }

    
    public function updateFields($set, bool $addToDefault = false, ?array $ignore = null)
    {
        if (! empty($set)) {
            if (! is_array($set)) {
                $set = explode(',', $set);
            }

            foreach ($set as $key => $value) {
                if (! ($value instanceof RawSql)) {
                    $value = $this->db->protectIdentifiers($value);
                }

                if (is_numeric($key)) {
                    $key = $value;
                }

                if ($ignore === null || ! in_array($key, $ignore, true)) {
                    if ($addToDefault) {
                        $this->QBOptions['updateFieldsAdditional'][$this->db->protectIdentifiers($key)] = $value;
                    } else {
                        $this->QBOptions['updateFields'][$this->db->protectIdentifiers($key)] = $value;
                    }
                }
            }

            if ($addToDefault === false && isset($this->QBOptions['updateFieldsAdditional'], $this->QBOptions['updateFields'])) {
                $this->QBOptions['updateFields'] = array_merge($this->QBOptions['updateFields'], $this->QBOptions['updateFieldsAdditional']);

                unset($this->QBOptions['updateFieldsAdditional']);
            }
        }

        return $this;
    }

    
    public function onConstraint($set)
    {
        if (! empty($set)) {
            if (is_string($set)) {
                $set = explode(',', $set);

                $set = array_map(trim(...), $set);
            }

            if ($set instanceof RawSql) {
                $set = [$set];
            }

            foreach ($set as $key => $value) {
                if (! ($value instanceof RawSql)) {
                    $value = $this->db->protectIdentifiers($value);
                }

                if (is_string($key)) {
                    $key = $this->db->protectIdentifiers($key);
                }

                $this->QBOptions['constraints'][$key] = $value;
            }
        }

        return $this;
    }

    
    public function setQueryAsData($query, ?string $alias = null, $columns = null): BaseBuilder
    {
        if (is_string($query)) {
            throw new InvalidArgumentException('$query parameter must be BaseBuilder or RawSql class.');
        }

        if ($query instanceof BaseBuilder) {
            $query = $query->getCompiledSelect();
        } elseif ($query instanceof RawSql) {
            $query = $query->__toString();
        }

        if (is_string($query)) {
            if ($columns !== null && is_string($columns)) {
                $columns = explode(',', $columns);
                $columns = array_map(trim(...), $columns);
            }

            $columns = (array) $columns;

            if ($columns === []) {
                $columns = $this->fieldsFromQuery($query);
            }

            if ($alias !== null) {
                $this->setAlias($alias);
            }

            foreach ($columns as $key => $value) {
                $columns[$key] = $this->db->escapeChar . $value . $this->db->escapeChar;
            }

            $this->QBOptions['setQueryAsData'] = $query;
            $this->QBKeys                      = $columns;
            $this->QBSet                       = [];
        }

        return $this;
    }

    
    protected function fieldsFromQuery(string $sql): array
    {
        return $this->db->query('SELECT * FROM (' . $sql . ') _u_ LIMIT 1')->getFieldNames();
    }

    
    protected function formatValues(array $values): array
    {
        return array_map(static fn ($index): string => '(' . implode(',', $index) . ')', $values);
    }

    
    public function insertBatch($set = null, ?bool $escape = null, int $batchSize = 100)
    {
        if (isset($this->QBOptions['setQueryAsData'])) {
            $sql = $this->_insertBatch($this->QBFrom[0], $this->QBKeys, []);

            if ($sql === '') {
                return false; 
            }

            if ($this->testMode === false) {
                $this->db->query($sql, null, false);
            }

            $this->resetWrite();

            return $this->testMode ? $sql : $this->db->affectedRows();
        }

        if ($set !== null && $set !== []) {
            $this->setData($set, $escape);
        }

        return $this->batchExecute('_insertBatch', $batchSize);
    }

    
    protected function _insertBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $sql = 'INSERT ' . $this->compileIgnore('insert') . 'INTO ' . $table
                . ' (' . implode(', ', $keys) . ")\n{:_table_:}";

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = 'VALUES ' . implode(', ', $this->formatValues($values));
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    public function setInsertBatch($key, string $value = '', ?bool $escape = null)
    {
        if (! is_array($key)) {
            $key = [[$key => $value]];
        }

        return $this->setData($key, $escape);
    }

    
    public function getCompiledInsert(bool $reset = true)
    {
        if ($this->validateInsert() === false) {
            return false;
        }

        $sql = $this->_insert(
            $this->db->protectIdentifiers(
                $this->removeAlias($this->QBFrom[0]),
                true,
                null,
                false,
            ),
            array_keys($this->QBSet),
            array_values($this->QBSet),
        );

        if ($reset) {
            $this->resetWrite();
        }

        return $this->compileFinalQuery($sql);
    }

    
    public function insert($set = null, ?bool $escape = null)
    {
        if ($set !== null) {
            $this->set($set, '', $escape);
        }

        if ($this->validateInsert() === false) {
            return false;
        }

        $sql = $this->_insert(
            $this->db->protectIdentifiers(
                $this->removeAlias($this->QBFrom[0]),
                true,
                $escape,
                false,
            ),
            array_keys($this->QBSet),
            array_values($this->QBSet),
        );

        if (! $this->testMode) {
            $this->resetWrite();

            $result = $this->db->query($sql, $this->binds, false);

            
            $this->binds = [];

            return $result;
        }

        return false;
    }

    
    protected function removeAlias(string $from): string
    {
        if (str_contains($from, ' ')) {
            
            $from = preg_replace('/\s+AS\s+/i', ' ', $from);

            $parts = explode(' ', $from);
            $from  = $parts[0];
        }

        return $from;
    }

    
    protected function validateInsert(): bool
    {
        if (empty($this->QBSet)) {
            if ($this->db->DBDebug) {
                throw new DatabaseException('You must use the "set" method to insert an entry.');
            }

            return false; 
        }

        return true;
    }

    
    protected function _insert(string $table, array $keys, array $unescapedKeys): string
    {
        return 'INSERT ' . $this->compileIgnore('insert') . 'INTO ' . $table . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $unescapedKeys) . ')';
    }

    
    public function replace(?array $set = null)
    {
        if ($set !== null) {
            $this->set($set);
        }

        if (empty($this->QBSet)) {
            if ($this->db->DBDebug) {
                throw new DatabaseException('You must use the "set" method to update an entry.');
            }

            return false; 
        }

        $table = $this->QBFrom[0];

        $sql = $this->_replace($table, array_keys($this->QBSet), array_values($this->QBSet));

        $this->resetWrite();

        return $this->testMode ? $sql : $this->db->query($sql, $this->binds, false);
    }

    
    protected function _replace(string $table, array $keys, array $values): string
    {
        return 'REPLACE INTO ' . $table . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ')';
    }

    
    protected function _fromTables(): string
    {
        return implode(', ', $this->QBFrom);
    }

    
    public function getCompiledUpdate(bool $reset = true)
    {
        if ($this->validateUpdate() === false) {
            return false;
        }

        $sql = $this->_update($this->QBFrom[0], $this->QBSet);

        if ($reset) {
            $this->resetWrite();
        }

        return $this->compileFinalQuery($sql);
    }

    
    public function update($set = null, $where = null, ?int $limit = null): bool
    {
        if ($set !== null) {
            $this->set($set);
        }

        if ($this->validateUpdate() === false) {
            return false;
        }

        if ($where !== null) {
            $this->where($where);
        }

        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll && $limit === 0) {
            $limit = null;
        }

        if ($limit !== null) {
            if (! $this->canLimitWhereUpdates) {
                throw new DatabaseException('This driver does not allow LIMITs on UPDATE queries using WHERE.');
            }

            $this->limit($limit);
        }

        $sql = $this->_update($this->QBFrom[0], $this->QBSet);

        if (! $this->testMode) {
            $this->resetWrite();

            $result = $this->db->query($sql, $this->binds, false);

            if ($result !== false) {
                
                $this->binds = [];

                return true;
            }

            return false;
        }

        return true;
    }

    
    protected function _update(string $table, array $values): string
    {
        $valStr = [];

        foreach ($values as $key => $val) {
            $valStr[] = $key . ' = ' . $val;
        }

        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll) {
            return 'UPDATE ' . $this->compileIgnore('update') . $table . ' SET ' . implode(', ', $valStr)
                . $this->compileWhereHaving('QBWhere')
                . $this->compileOrderBy()
                . ($this->QBLimit ? $this->_limit(' ', true) : '');
        }

        return 'UPDATE ' . $this->compileIgnore('update') . $table . ' SET ' . implode(', ', $valStr)
            . $this->compileWhereHaving('QBWhere')
            . $this->compileOrderBy()
            . ($this->QBLimit !== false ? $this->_limit(' ', true) : '');
    }

    
    protected function validateUpdate(): bool
    {
        if (empty($this->QBSet)) {
            if ($this->db->DBDebug) {
                throw new DatabaseException('You must use the "set" method to update an entry.');
            }

            return false; 
        }

        return true;
    }

    
    public function updateBatch($set = null, $constraints = null, int $batchSize = 100)
    {
        $this->onConstraint($constraints);

        if (isset($this->QBOptions['setQueryAsData'])) {
            $sql = $this->_updateBatch($this->QBFrom[0], $this->QBKeys, []);

            if ($sql === '') {
                return false; 
            }

            if ($this->testMode === false) {
                $this->db->query($sql, null, false);
            }

            $this->resetWrite();

            return $this->testMode ? $sql : $this->db->affectedRows();
        }

        if ($set !== null && $set !== []) {
            $this->setData($set, true);
        }

        return $this->batchExecute('_updateBatch', $batchSize);
    }

    
    protected function _updateBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $constraints = $this->QBOptions['constraints'] ?? [];

            if ($constraints === []) {
                if ($this->db->DBDebug) {
                    throw new DatabaseException('You must specify a constraint to match on for batch updates.'); 
                }

                return ''; 
            }

            $updateFields = $this->QBOptions['updateFields'] ??
                $this->updateFields($keys, false, $constraints)->QBOptions['updateFields'] ??
                [];

            $alias = $this->QBOptions['alias'] ?? '_u';

            $sql = 'UPDATE ' . $this->compileIgnore('update') . $table . "\n";

            $sql .= "SET\n";

            $sql .= implode(
                ",\n",
                array_map(
                    static fn ($key, $value): string => $key . ($value instanceof RawSql ?
                        ' = ' . $value :
                        ' = ' . $alias . '.' . $value),
                    array_keys($updateFields),
                    $updateFields,
                ),
            ) . "\n";

            $sql .= "FROM (\n{:_table_:}";

            $sql .= ') ' . $alias . "\n";

            $sql .= 'WHERE ' . implode(
                ' AND ',
                array_map(
                    static fn ($key, $value) => (
                        ($value instanceof RawSql && is_string($key))
                        ?
                        $table . '.' . $key . ' = ' . $value
                        :
                        (
                            $value instanceof RawSql
                            ?
                            $value
                            :
                            $table . '.' . $value . ' = ' . $alias . '.' . $value
                        )
                    ),
                    array_keys($constraints),
                    $constraints,
                ),
            );

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = implode(
                " UNION ALL\n",
                array_map(
                    static fn ($value): string => 'SELECT ' . implode(', ', array_map(
                        static fn ($key, $index): string => $index . ' ' . $key,
                        $keys,
                        $value,
                    )),
                    $values,
                ),
            ) . "\n";
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    public function setUpdateBatch($key, string $index = '', ?bool $escape = null)
    {
        if ($index !== '') {
            $this->onConstraint($index);
        }

        $this->setData($key, $escape);

        return $this;
    }

    
    public function emptyTable()
    {
        $table = $this->QBFrom[0];

        $sql = $this->_delete($table);

        if ($this->testMode) {
            return $sql;
        }

        $this->resetWrite();

        return $this->db->query($sql, null, false);
    }

    
    public function truncate()
    {
        $table = $this->QBFrom[0];

        $sql = $this->_truncate($table);

        if ($this->testMode) {
            return $sql;
        }

        $this->resetWrite();

        return $this->db->query($sql, null, false);
    }

    
    protected function _truncate(string $table): string
    {
        return 'TRUNCATE ' . $table;
    }

    
    public function getCompiledDelete(bool $reset = true): string
    {
        $sql = $this->testMode()->delete('', null, $reset);
        $this->testMode(false);

        return $this->compileFinalQuery($sql);
    }

    
    public function delete($where = '', ?int $limit = null, bool $resetData = true)
    {
        $table = $this->db->protectIdentifiers($this->QBFrom[0], true, null, false);

        if ($where !== '') {
            $this->where($where);
        }

        if (empty($this->QBWhere)) {
            if ($this->db->DBDebug) {
                throw new DatabaseException('Deletes are not allowed unless they contain a "where" or "like" clause.');
            }

            return false; 
        }

        $sql = $this->_delete($this->removeAlias($table));

        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll && $limit === 0) {
            $limit = null;
        }

        if ($limit !== null) {
            $this->QBLimit = $limit;
        }

        if (! empty($this->QBLimit)) {
            if (! $this->canLimitDeletes) {
                throw new DatabaseException('SQLite3 does not allow LIMITs on DELETE queries.');
            }

            $sql = $this->_limit($sql, true);
        }

        if ($resetData) {
            $this->resetWrite();
        }

        return $this->testMode ? $sql : $this->db->query($sql, $this->binds, false);
    }

    
    public function deleteBatch($set = null, $constraints = null, int $batchSize = 100)
    {
        $this->onConstraint($constraints);

        if (isset($this->QBOptions['setQueryAsData'])) {
            $sql = $this->_deleteBatch($this->QBFrom[0], $this->QBKeys, []);

            if ($sql === '') {
                return false; 
            }

            if ($this->testMode === false) {
                $this->db->query($sql, null, false);
            }

            $this->resetWrite();

            return $this->testMode ? $sql : $this->db->affectedRows();
        }

        if ($set !== null && $set !== []) {
            $this->setData($set, true);
        }

        return $this->batchExecute('_deleteBatch', $batchSize);
    }

    
    protected function _deleteBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $constraints = $this->QBOptions['constraints'] ?? [];

            if ($constraints === []) {
                if ($this->db->DBDebug) {
                    throw new DatabaseException('You must specify a constraint to match on for batch deletes.'); 
                }

                return ''; 
            }

            $alias = $this->QBOptions['alias'] ?? '_u';

            $sql = 'DELETE ' . $table . ' FROM ' . $table . "\n";

            $sql .= "INNER JOIN (\n{:_table_:}";

            $sql .= ') ' . $alias . "\n";

            $sql .= 'ON ' . implode(
                ' AND ',
                array_map(
                    static fn ($key, $value) => (
                        $value instanceof RawSql ?
                        $value :
                        (
                            is_string($key) ?
                            $table . '.' . $key . ' = ' . $alias . '.' . $value :
                            $table . '.' . $value . ' = ' . $alias . '.' . $value
                        )
                    ),
                    array_keys($constraints),
                    $constraints,
                ),
            );

            
            foreach ($this->QBWhere as $key => $where) {
                foreach ($this->binds as $field => $bind) {
                    $this->QBWhere[$key]['condition'] = str_replace(':' . $field . ':', $bind[0], $where['condition']);
                }
            }

            $sql .= ' ' . $this->compileWhereHaving('QBWhere');

            $this->QBOptions['sql'] = trim($sql);
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = implode(
                " UNION ALL\n",
                array_map(
                    static fn ($value): string => 'SELECT ' . implode(', ', array_map(
                        static fn ($key, $index): string => $index . ' ' . $key,
                        $keys,
                        $value,
                    )),
                    $values,
                ),
            ) . "\n";
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    public function increment(string $column, int $value = 1)
    {
        $column = $this->db->protectIdentifiers($column);

        $sql = $this->_update($this->QBFrom[0], [$column => "{$column} + {$value}"]);

        if (! $this->testMode) {
            $this->resetWrite();

            return $this->db->query($sql, $this->binds, false);
        }

        return true;
    }

    
    public function decrement(string $column, int $value = 1)
    {
        $column = $this->db->protectIdentifiers($column);

        $sql = $this->_update($this->QBFrom[0], [$column => "{$column}-{$value}"]);

        if (! $this->testMode) {
            $this->resetWrite();

            return $this->db->query($sql, $this->binds, false);
        }

        return true;
    }

    
    protected function _delete(string $table): string
    {
        return 'DELETE ' . $this->compileIgnore('delete') . 'FROM ' . $table . $this->compileWhereHaving('QBWhere');
    }

    
    protected function trackAliases($table)
    {
        if (is_array($table)) {
            foreach ($table as $t) {
                $this->trackAliases($t);
            }

            return null;
        }

        
        
        if (str_contains($table, ',')) {
            return $this->trackAliases(explode(',', $table));
        }

        
        if (str_contains($table, ' ')) {
            
            $table = preg_replace('/\s+AS\s+/i', ' ', $table);

            
            $alias = trim(strrchr($table, ' '));

            
            $this->db->addTableAlias($alias);
        }

        return null;
    }

    
    protected function compileSelect($selectOverride = false): string
    {
        if ($selectOverride !== false) {
            $sql = $selectOverride;
        } else {
            $sql = $this->QBDistinct ? 'SELECT DISTINCT ' : 'SELECT ';

            if (empty($this->QBSelect)) {
                $sql .= '*';
            } else {
                
                
                
                foreach ($this->QBSelect as $key => $val) {
                    if ($val instanceof RawSql) {
                        $this->QBSelect[$key] = (string) $val;
                    } else {
                        $protect              = $this->QBNoEscape[$key] ?? null;
                        $this->QBSelect[$key] = $this->db->protectIdentifiers($val, false, $protect);
                    }
                }

                $sql .= implode(', ', $this->QBSelect);
            }
        }

        if (! empty($this->QBFrom)) {
            $sql .= "\nFROM " . $this->_fromTables();
        }

        if (! empty($this->QBJoin)) {
            $sql .= "\n" . implode("\n", $this->QBJoin);
        }

        $sql .= $this->compileWhereHaving('QBWhere')
            . $this->compileGroupBy()
            . $this->compileWhereHaving('QBHaving')
            . $this->compileOrderBy();

        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll) {
            if ($this->QBLimit) {
                $sql = $this->_limit($sql . "\n");
            }
        } elseif ($this->QBLimit !== false || $this->QBOffset) {
            $sql = $this->_limit($sql . "\n");
        }

        return $this->unionInjection($sql);
    }

    
    protected function compileIgnore(string $statement)
    {
        if ($this->QBIgnore && isset($this->supportedIgnoreStatements[$statement])) {
            return trim($this->supportedIgnoreStatements[$statement]) . ' ';
        }

        return '';
    }

    
    protected function compileWhereHaving(string $qbKey): string
    {
        if (! empty($this->{$qbKey})) {
            foreach ($this->{$qbKey} as &$qbkey) {
                
                if (is_string($qbkey)) {
                    continue;
                }

                if ($qbkey instanceof RawSql) {
                    continue;
                }

                if ($qbkey['condition'] instanceof RawSql) {
                    $qbkey = $qbkey['condition'];

                    continue;
                }

                if ($qbkey['escape'] === false) {
                    $qbkey = $qbkey['condition'];

                    continue;
                }

                
                $conditions = preg_split(
                    '/((?:^|\s+)AND\s+|(?:^|\s+)OR\s+)/i',
                    $qbkey['condition'],
                    -1,
                    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
                );

                foreach ($conditions as &$condition) {
                    $op = $this->getOperator($condition);
                    if (
                        $op === false
                        || preg_match(
                            '/^(\(?)(.*)(' . preg_quote($op, '/') . ')\s*(.*(?<!\)))?(\)?)$/i',
                            $condition,
                            $matches,
                        ) !== 1
                    ) {
                        continue;
                    }

                    
                    
                    
                    
                    
                    
                    
                    

                    if ($matches[4] !== '') {
                        $protectIdentifiers = false;
                        if (str_contains($matches[4], '.')) {
                            $protectIdentifiers = true;
                        }

                        if (! str_contains($matches[4], ':')) {
                            $matches[4] = $this->db->protectIdentifiers(trim($matches[4]), false, $protectIdentifiers);
                        }

                        $matches[4] = ' ' . $matches[4];
                    }

                    $condition = $matches[1] . $this->db->protectIdentifiers(trim($matches[2]))
                        . ' ' . trim($matches[3]) . $matches[4] . $matches[5];
                }

                $qbkey = implode('', $conditions);
            }

            return ($qbKey === 'QBHaving' ? "\nHAVING " : "\nWHERE ")
                . implode("\n", $this->{$qbKey});
        }

        return '';
    }

    
    protected function compileGroupBy(): string
    {
        if (! empty($this->QBGroupBy)) {
            foreach ($this->QBGroupBy as &$groupBy) {
                
                if (is_string($groupBy)) {
                    continue;
                }

                $groupBy = ($groupBy['escape'] === false || $this->isLiteral($groupBy['field']))
                    ? $groupBy['field']
                    : $this->db->protectIdentifiers($groupBy['field']);
            }

            return "\nGROUP BY " . implode(', ', $this->QBGroupBy);
        }

        return '';
    }

    
    protected function compileOrderBy(): string
    {
        if (is_array($this->QBOrderBy) && $this->QBOrderBy !== []) {
            foreach ($this->QBOrderBy as &$orderBy) {
                if (is_string($orderBy)) {
                    continue;
                }
                if ($orderBy['escape'] !== false && ! $this->isLiteral($orderBy['field'])) {
                    $orderBy['field'] = $this->db->protectIdentifiers($orderBy['field']);
                }

                $orderBy = $orderBy['field'] . $orderBy['direction'];
            }

            return "\nORDER BY " . implode(', ', $this->QBOrderBy);
        }

        return '';
    }

    protected function unionInjection(string $sql): string
    {
        if ($this->QBUnion === []) {
            return $sql;
        }

        return 'SELECT * FROM (' . $sql . ') '
            . ($this->db->protectIdentifiers ? $this->db->escapeIdentifiers('uwrp0') : 'uwrp0')
            . implode("\n", $this->QBUnion);
    }

    
    protected function objectToArray($object)
    {
        if (! is_object($object)) {
            return $object;
        }

        if ($object instanceof RawSql) {
            throw new InvalidArgumentException('RawSql "' . $object . '" cannot be used here.');
        }

        $array = [];

        foreach (get_object_vars($object) as $key => $val) {
            if ((! is_object($val) || $val instanceof RawSql) && ! is_array($val)) {
                $array[$key] = $val;
            }
        }

        return $array;
    }

    
    protected function batchObjectToArray($object)
    {
        if (! is_object($object)) {
            return $object;
        }

        $array  = [];
        $out    = get_object_vars($object);
        $fields = array_keys($out);

        foreach ($fields as $val) {
            $i = 0;

            foreach ($out[$val] as $data) {
                $array[$i++][$val] = $data;
            }
        }

        return $array;
    }

    
    protected function isLiteral(string $str): bool
    {
        $str = trim($str);

        if ($str === ''
            || ctype_digit($str)
            || (string) (float) $str === $str
            || in_array(strtoupper($str), ['TRUE', 'FALSE'], true)
        ) {
            return true;
        }

        if ($this->isLiteralStr === []) {
            $this->isLiteralStr = $this->db->escapeChar !== '"' ? ['"', "'"] : ["'"];
        }

        return in_array($str[0], $this->isLiteralStr, true);
    }

    
    public function resetQuery()
    {
        $this->resetSelect();
        $this->resetWrite();

        return $this;
    }

    
    protected function resetRun(array $qbResetItems)
    {
        foreach ($qbResetItems as $item => $defaultValue) {
            $this->{$item} = $defaultValue;
        }
    }

    
    protected function resetSelect()
    {
        $this->resetRun([
            'QBSelect'   => [],
            'QBJoin'     => [],
            'QBWhere'    => [],
            'QBGroupBy'  => [],
            'QBHaving'   => [],
            'QBOrderBy'  => [],
            'QBNoEscape' => [],
            'QBDistinct' => false,
            'QBLimit'    => false,
            'QBOffset'   => false,
            'QBUnion'    => [],
        ]);

        if ($this->db instanceof BaseConnection) {
            $this->db->setAliasedTables([]);
        }

        
        if (! empty($this->QBFrom)) {
            $this->from(array_shift($this->QBFrom), true);
        }
    }

    
    protected function resetWrite()
    {
        $this->resetRun([
            'QBSet'     => [],
            'QBJoin'    => [],
            'QBWhere'   => [],
            'QBOrderBy' => [],
            'QBKeys'    => [],
            'QBLimit'   => false,
            'QBIgnore'  => false,
            'QBOptions' => [],
        ]);
    }

    
    protected function hasOperator(string $str): bool
    {
        return preg_match(
            '/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i',
            trim($str),
        ) === 1;
    }

    
    protected function getOperator(string $str, bool $list = false)
    {
        if ($this->pregOperators === []) {
            $_les = $this->db->likeEscapeStr !== ''
                ? '\s+' . preg_quote(trim(sprintf($this->db->likeEscapeStr, $this->db->likeEscapeChar)), '/')
                : '';
            $this->pregOperators = [
                '\s*(?:<|>|!)?=\s*', 
                '\s*<>?\s*',         
                '\s*>\s*',           
                '\s+IS NULL',             
                '\s+IS NOT NULL',         
                '\s+EXISTS\s*\(.*\)',     
                '\s+NOT EXISTS\s*\(.*\)', 
                '\s+BETWEEN\s+',          
                '\s+IN\s*\(.*\)',         
                '\s+NOT IN\s*\(.*\)',     
                '\s+LIKE\s+\S.*(' . $_les . ')?',     
                '\s+NOT LIKE\s+\S.*(' . $_les . ')?', 
            ];
        }

        return preg_match_all(
            '/' . implode('|', $this->pregOperators) . '/i',
            $str,
            $match,
        ) >= 1 ? ($list ? $match[0] : $match[0][0]) : false;
    }

    
    private function getOperatorFromWhereKey(string $whereKey)
    {
        $whereKey = trim($whereKey);

        $pregOperators = [
            '\s*(?:<|>|!)?=',         
            '\s*<>?',                 
            '\s*>',                   
            '\s+IS NULL',             
            '\s+IS NOT NULL',         
            '\s+EXISTS\s*\(.*\)',     
            '\s+NOT EXISTS\s*\(.*\)', 
            '\s+BETWEEN\s+',          
            '\s+IN\s*\(.*\)',         
            '\s+NOT IN\s*\(.*\)',     
            '\s+LIKE',                
            '\s+NOT LIKE',            
        ];

        return preg_match_all(
            '/' . implode('|', $pregOperators) . '/i',
            $whereKey,
            $match,
        ) >= 1 ? $match[0] : false;
    }

    
    protected function setBind(string $key, $value = null, bool $escape = true): string
    {
        if (! array_key_exists($key, $this->binds)) {
            $this->binds[$key] = [
                $value,
                $escape,
            ];

            return $key;
        }

        if (! array_key_exists($key, $this->bindsKeyCount)) {
            $this->bindsKeyCount[$key] = 1;
        }

        $count = $this->bindsKeyCount[$key]++;

        $this->binds[$key . '.' . $count] = [
            $value,
            $escape,
        ];

        return $key . '.' . $count;
    }

    
    protected function cleanClone()
    {
        return (clone $this)->from([], true)->resetQuery();
    }

    
    protected function isSubquery($value): bool
    {
        return $value instanceof BaseBuilder || $value instanceof Closure;
    }

    
    protected function buildSubquery($builder, bool $wrapped = false, string $alias = ''): string
    {
        if ($builder instanceof Closure) {
            $builder($builder = $this->db->newQuery());
        }

        if ($builder === $this) {
            throw new DatabaseException('The subquery cannot be the same object as the main query object.');
        }

        $subquery = strtr($builder->getCompiledSelect(false), "\n", ' ');

        if ($wrapped) {
            $subquery = '(' . $subquery . ')';
            $alias    = trim($alias);

            if ($alias !== '') {
                $subquery .= ' ' . ($this->db->protectIdentifiers ? $this->db->escapeIdentifiers($alias) : $alias);
            }
        }

        return $subquery;
    }
}
