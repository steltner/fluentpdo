<?php declare(strict_types=1);

namespace Envms\FluentPDO\Queries;

use Envms\FluentPDO\{Exception, Literal, Query};
use PDOStatement;

/**
 * UPDATE query builder
 *
 * @method Update  leftJoin(string $statement) add LEFT JOIN to query
 *                        ($statement can be 'table' name only or 'table:' means back reference)
 * @method Update  innerJoin(string $statement) add INNER JOIN to query
 *                        ($statement can be 'table' name only or 'table:' means back reference)
 * @method Update  orderBy(string $column) add ORDER BY to query
 * @method Update  limit(int $limit) add LIMIT to query
 */
class Update extends Common
{
    public function __construct(Query $fluent, string $table)
    {
        $clauses = [
            'UPDATE'   => [$this, 'getClauseUpdate'],
            'JOIN'     => [$this, 'getClauseJoin'],
            'SET'      => [$this, 'getClauseSet'],
            'WHERE'    => [$this, 'getClauseWhere'],
            'ORDER BY' => ', ',
            'LIMIT'    => null,
        ];
        parent::__construct($fluent, $clauses);

        $this->statements['UPDATE'] = $table;

        $tableParts = explode(' ', $table);
        $this->joins[] = end($tableParts);
    }

    /**
     * In Update's case, parameters are not assigned until the query is built, since this method
     *
     * @param string|array $fieldOrArray
     * @param bool|string  $value
     *
     * @throws Exception
     *
     * @return $this
     */
    public function set($fieldOrArray, $value = false): self
    {
        if (!$fieldOrArray) {
            return $this;
        }
        if (is_string($fieldOrArray) && $value !== false) {
            $this->statements['SET'][$fieldOrArray] = $value;
        } else {
            if (!is_array($fieldOrArray)) {
                throw new Exception('You must pass a value, or provide the SET list as an associative array. column => value');
            } else {
                foreach ($fieldOrArray as $field => $value) {
                    $this->statements['SET'][$field] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Execute update query
     *
     * @param bool $getResultAsPdoStatement true to return the pdo statement instead of row count
     *
     * @throws Exception
     *
     * @return int|null|PDOStatement
     */
    public function execute(bool $getResultAsPdoStatement = false)
    {
        if (empty($this->statements['WHERE'])) {
            throw new Exception('Update queries must contain a WHERE clause to prevent unwanted data loss');
        }

        $result = parent::execute();

        if ($getResultAsPdoStatement) {
            return $result;
        }

        return isset($result) ? $result->rowCount() : null;
    }

    protected function getClauseUpdate(): string
    {
        return 'UPDATE ' . $this->statements['UPDATE'];
    }

    protected function getClauseSet(): string
    {
        $setArray = [];
        foreach ($this->statements['SET'] as $field => $value) {
            // named params are being used here
            if (is_array($value) && strpos(key($value), ':') === 0) {
                $key = key($value);
                $setArray[] = $field . ' = ' . $key;
                $this->parameters['SET'][$key] = $value[$key];
            }
            elseif ($value instanceof Literal) {
                $setArray[] = $field . ' = ' . $value;
            } else {
                $setArray[] = $field . ' = ?';
                $this->parameters['SET'][$field] = $value;
            }
        }

        return ' SET ' . implode(', ', $setArray);
    }

}
