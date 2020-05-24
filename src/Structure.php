<?php

namespace Envms\FluentPDO;

use function is_callable;
use function sprintf;

class Structure
{
    private string $primaryKey;
    /** @var array|string */
    private $foreignKey;

    /**
     * @param string $primaryKey
     * @param string|array $foreignKey
     */
    public function __construct(string $primaryKey = 'id', $foreignKey = '%s_id')
    {
        if ($foreignKey === null) {
            $foreignKey = $primaryKey;
        }
        $this->primaryKey = $primaryKey;
        $this->foreignKey = $foreignKey;
    }

    public function getPrimaryKey(string $table): string
    {
        return $this->key($this->primaryKey, $table);
    }

    public function getForeignKey(string $table): string
    {
        return $this->key($this->foreignKey, $table);
    }

    /**
     * @param string|callback $key
     * @param string $table
     *
     * @return string|array
     */
    private function key($key, string $table)
    {
        if (is_callable($key)) {
            return $key($table);
        }

        return sprintf($key, $table);
    }
}
