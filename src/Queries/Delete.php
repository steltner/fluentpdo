<?php declare(strict_types=1);

namespace Envms\FluentPDO\Queries;

use Envms\FluentPDO\{Exception, Query};

/**
 * DELETE query builder
 *
 * @method Delete  leftJoin(string $statement) add LEFT JOIN to query
 *                        ($statement can be 'table' name only or 'table:' means back reference)
 * @method Delete  innerJoin(string $statement) add INNER JOIN to query
 *                        ($statement can be 'table' name only or 'table:' means back reference)
 * @method Delete  from(string $table) add LIMIT to query
 * @method Delete  orderBy(string $column) add ORDER BY to query
 * @method Delete  limit(int $limit) add LIMIT to query
 */
class Delete extends Common
{
    private bool $ignore = false;

    public function __construct(Query $fluent, string $table)
    {
        $clauses = [
            'DELETE FROM' => [$this, 'getClauseDeleteFrom'],
            'DELETE' => [$this, 'getClauseDelete'],
            'FROM' => null,
            'JOIN' => [$this, 'getClauseJoin'],
            'WHERE' => [$this, 'getClauseWhere'],
            'ORDER BY' => ', ',
            'LIMIT' => null,
        ];

        parent::__construct($fluent, $clauses);

        $this->statements['DELETE FROM'] = $table;
        $this->statements['DELETE'] = $table;
    }

    public function ignore(): self
    {
        $this->ignore = true;

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function buildQuery(): string
    {
        if ($this->statements['FROM']) {
            unset($this->clauses['DELETE FROM']);
        } else {
            unset($this->clauses['DELETE']);
        }

        return parent::buildQuery();
    }

    /**
     * Execute DELETE query
     *
     * @return int|null
     * @throws Exception
     *
     */
    public function execute()
    {
        if (empty($this->statements['WHERE'])) {
            throw new Exception('Delete queries must contain a WHERE clause to prevent unwanted data loss');
        }

        $result = parent::execute();

        return isset($result) ? $result->rowCount() : null;
    }

    protected function getClauseDelete(): string
    {
        return 'DELETE' . ($this->ignore ? " IGNORE" : '') . ' ' . $this->statements['DELETE'];
    }

    protected function getClauseDeleteFrom(): string
    {
        return 'DELETE' . ($this->ignore ? " IGNORE" : '') . ' FROM ' . $this->statements['DELETE FROM'];
    }
}
