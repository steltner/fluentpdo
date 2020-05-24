<?php

namespace Envms\FluentPDO\Queries;

use Envms\FluentPDO\{Exception, Literal, Query};

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function current;
use function implode;
use function is_array;
use function is_string;
use function key;

class Insert extends Base
{
    private array $columns = [];
    private array $firstValue = [];
    private bool $ignore = false;
    private bool $delayed = false;

    /**
     * @param Query $fluent
     * @param string $table
     * @param array $values
     *
     * @throws Exception
     */
    public function __construct(Query $fluent, string $table, array $values)
    {
        $clauses = [
            'INSERT INTO' => [$this, 'getClauseInsertInto'],
            'VALUES' => [$this, 'getClauseValues'],
            'ON DUPLICATE KEY UPDATE' => [$this, 'getClauseOnDuplicateKeyUpdate'],
        ];
        parent::__construct($fluent, $clauses);

        $this->statements['INSERT INTO'] = $table;
        $this->values($values);
    }

    public function ignore(): self
    {
        $this->ignore = true;

        return $this;
    }

    public function delayed(): self
    {
        $this->delayed = true;

        return $this;
    }

    /**
     * Add VALUES
     *
     * @param array $values
     *
     * @return Insert
     * @throws Exception
     */
    public function values(array $values): self
    {
        $first = current($values);
        if (is_string(key($values))) {
            // is one row array
            $this->addOneValue($values);
        } elseif (is_array($first) && is_string(key($first))) {
            // this is multi values
            foreach ($values as $oneValue) {
                $this->addOneValue($oneValue);
            }
        }

        return $this;
    }

    /**
     * Add ON DUPLICATE KEY UPDATE
     *
     * @param array $values
     *
     * @return Insert
     */
    public function onDuplicateKeyUpdate(array $values): self
    {
        $this->statements['ON DUPLICATE KEY UPDATE'] = array_merge(
            $this->statements['ON DUPLICATE KEY UPDATE'], $values
        );

        return $this;
    }

    /**
     * Execute insert query
     *
     * @param mixed $sequence
     *
     * @return int|false - Last inserted primary key
     * @throws Exception
     *
     */
    public function execute($sequence = null)
    {
        $result = parent::execute();

        if ($result) {
            return $this->fluent->getPdo()->lastInsertId($sequence);
        }

        return false;
    }

    /**
     * @param $sequence
     *
     * @return bool
     * @throws Exception
     *
     */
    public function executeWithoutId($sequence = null): bool
    {
        $result = parent::execute();

        return $result !== false;
    }

    protected function getClauseInsertInto(): string
    {
        return 'INSERT' . ($this->ignore ? " IGNORE" : '') . ($this->delayed ? " DELAYED" : '') . ' INTO ' . $this->statements['INSERT INTO'];
    }

    protected function getClauseValues(): string
    {
        $valuesArray = [];
        foreach ($this->statements['VALUES'] as $rows) {
            // literals should not be parametrized.
            // They are commonly used to call engine functions or literals.
            // Eg: NOW(), CURRENT_TIMESTAMP etc
            $placeholders = array_map([$this, 'parameterGetValue'], $rows);
            $valuesArray[] = '(' . implode(', ', $placeholders) . ')';
        }

        $columns = implode(', ', $this->columns);
        $values = implode(', ', $valuesArray);

        return " ($columns) VALUES $values";
    }

    protected function getClauseOnDuplicateKeyUpdate(): string
    {
        $result = [];
        foreach ($this->statements['ON DUPLICATE KEY UPDATE'] as $key => $value) {
            $result[] = "$key = " . $this->parameterGetValue($value);
        }

        return ' ON DUPLICATE KEY UPDATE ' . implode(', ', $result);
    }

    /**
     * @param Literal|mixed $param
     *
     * @return string
     */
    protected function parameterGetValue($param): string
    {
        return $param instanceof Literal ? (string)$param : '?';
    }

    /**
     * Removes all Literal instances from the argument
     * since they are not to be used as PDO parameters but rather injected directly into the query
     *
     * @param array $statements
     *
     * @return array
     */
    protected function filterLiterals(array $statements): array
    {
        $f = function ($item) {
            return !$item instanceof Literal;
        };

        return array_map(function ($item) use ($f) {
            if (is_array($item)) {
                return array_filter($item, $f);
            }

            return $item;
        }, array_filter($statements, $f));
    }

    protected function buildParameters(): array
    {
        $this->parameters = array_merge(
            $this->filterLiterals($this->statements['VALUES']),
            $this->filterLiterals($this->statements['ON DUPLICATE KEY UPDATE'])
        );

        return parent::buildParameters();
    }

    /**
     * @param array $oneValue
     *
     * @throws Exception
     */
    private function addOneValue(array $oneValue): void
    {
        // check if all $keys are strings
        foreach ($oneValue as $key => $value) {
            if (!is_string($key)) {
                throw new Exception('INSERT query: All keys of value array have to be strings.');
            }
        }
        if (!$this->firstValue) {
            $this->firstValue = $oneValue;
        }
        if (!$this->columns) {
            $this->columns = array_keys($oneValue);
        }
        if ($this->columns != array_keys($oneValue)) {
            throw new Exception('INSERT query: All VALUES have to same keys (columns).');
        }
        $this->statements['VALUES'][] = $oneValue;
    }
}
