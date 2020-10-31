<?php declare(strict_types=1);

namespace Envms\FluentPDO\Queries;

use ArrayIterator;
use Countable;
use Envms\FluentPDO\{Exception, Query, Utilities};
use PDO;
use PDOStatement;

class Select extends Common implements Countable
{
    /** @var mixed */
    private $fromTable;
    /** @var mixed */
    private $fromAlias;

    public function __construct(Query $fluent, string $from)
    {
        $clauses = [
            'SELECT'   => ', ',
            'FROM'     => null,
            'JOIN'     => [$this, 'getClauseJoin'],
            'WHERE'    => [$this, 'getClauseWhere'],
            'GROUP BY' => ',',
            'HAVING'   => ' AND ',
            'ORDER BY' => ', ',
            'LIMIT'    => null,
            'OFFSET'   => null,
            "\n--"     => "\n--"
        ];
        parent::__construct($fluent, $clauses);

        // initialize statements
        $fromParts = explode(' ', $from);
        $this->fromTable = reset($fromParts);
        $this->fromAlias = end($fromParts);

        $this->statements['FROM'] = $from;
        $this->statements['SELECT'][] = $this->fromAlias . '.*';
        $this->joins[] = $this->fromAlias;
    }

    /**
     * @param mixed $columns
     * @param bool  $overrideDefault
     *
     * @return $this
     */
    public function select($columns, bool $overrideDefault = false)
    {
        if ($overrideDefault === true) {
            $this->resetClause('SELECT');
        } elseif ($columns === null) {
            return $this->resetClause('SELECT');
        }

        $this->addStatement('SELECT', $columns, []);

        return $this;
    }

    public function getFromTable()
    {
        return $this->fromTable;
    }

    public function getFromAlias()
    {
        return $this->fromAlias;
    }

    /**
     * Returns a single column
     *
     * @param int $columnNumber
     *
     * @throws Exception
     *
     * @return string|null
     */
    public function fetchColumn(int $columnNumber = 0): ?string
    {
        $result = $this->execute();

        if (!isset($result)) {
            return null;
        }

        $column = $result->fetchColumn($columnNumber);

        if ($column === false) {
            return null;
        }

        return $column;
    }

    /**
     * Fetch first row or column
     *
     * @param string $column - column name or empty string for the whole row
     * @param int    $cursorOrientation
     *
     * @throws Exception
     *
     * @return mixed string, array or null if there is no row
     */
    public function fetch(?string $column = null, int $cursorOrientation = PDO::FETCH_ORI_NEXT)
    {
        if ($this->result === null) {
            $this->execute();
        }

        if (!isset($this->result)) {
            return null;
        }

        $row = $this->result->fetch($this->currentFetchMode, $cursorOrientation);

        if ($this->fluent->convertRead === true) {
            $row = Utilities::stringToNumeric($this->result, $row);
        }

        if (!$row) {
            return null;
        }

        if ($column !== null) {
            if (is_object($row)) {
                return $row->{$column};
            } else {
                return $row[$column];
            }
        }

        return $row;
    }

    /**
     * Fetch pairs
     *
     * @param $key
     * @param $value
     * @param $object
     *
     * @throws Exception
     *
     * @return array|PDOStatement
     */
    public function fetchPairs($key, $value, $object = false)
    {
        if (($s = $this->select("$key, $value", true)->asObject($object)->execute()) !== false) {
            return $s->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        return $s;
    }

    /**
     * Fetch all row
     *
     * @param string $index      - specify index column. Allows for data organization by field using 'field[]'
     * @param string $selectOnly - select columns which could be fetched
     *
     * @throws Exception
     *
     * @return array|null -  fetched rows
     */
    public function fetchAll(string $index = '', string $selectOnly = '')
    {
        $indexAsArray = strpos($index, '[]');

        if ($indexAsArray !== false) {
            $index = str_replace('[]', '', $index);
        }

        if ($selectOnly) {
            $this->select($index . ', ' . $selectOnly, true);
        }

        if ($index) {
            return $this->buildSelectData($index, $indexAsArray);
        } else {
            $result = $this->execute();

            if (!isset($result)) {
                return null;
            }

            if ($this->fluent->convertRead === true) {
                return Utilities::stringToNumeric($result, $result->fetchAll());
            } else {
                return $result->fetchAll();
            }
        }
    }

    /**
     * Countable interface doesn't break current select query
     *
     * @throws Exception
     *
     * @return int
     */
    public function count(): int
    {
        $fluent = clone $this;

        return (int)$fluent->select('COUNT(*)', true)->fetchColumn();
    }

    /**
     * @throws Exception
     *
     * @return ArrayIterator|PDOStatement
     */
    public function getIterator()
    {
        if ($this->fluent->convertRead === true) {
            return new ArrayIterator($this->fetchAll());
        } else {
            return $this->execute();
        }
    }

    private function buildSelectData(string $index, bool $indexAsArray): array
    {
        $data = [];

        foreach ($this as $row) {
            if (is_object($row)) {
                $key = $row->{$index};
            } else {
                $key = $row[$index];
            }

            if ($indexAsArray) {
                $data[$key][] = $row;
            } else {
                $data[$key] = $row;
            }
        }

        return $data;
    }
}
