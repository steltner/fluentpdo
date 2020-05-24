<?php

namespace Envms\FluentPDO\Queries;

use Envms\FluentPDO\Query;

class Json extends Common
{
    /** @var mixed */
    private $fromTable;
    /** @var mixed */
    private $fromAlias;
    private bool $convertTypes;

    public function __construct(Query $fluent, string $table)
    {
        $clauses = [
            'SELECT'   => ', ',
            'JOIN'     => [$this, 'getClauseJoin'],
            'WHERE'    => [$this, 'getClauseWhere'],
            'GROUP BY' => ',',
            'HAVING'   => ' AND ',
            'ORDER BY' => ', ',
            'LIMIT'    => null,
            'OFFSET'   => null,
            "\n--"     => "\n--",
        ];

        parent::__construct($fluent, $clauses);

        // initialize statements
        $tableParts = explode(' ', $table);
        $this->fromTable = reset($tableParts);
        $this->fromAlias = end($tableParts);

        $this->statements['SELECT'][] = '';
        $this->joins[] = $this->fromAlias;

        if (isset($fluent->convertTypes) && $fluent->convertTypes) {
            $this->convertTypes = true;
        }
    }
}
