<?php

namespace Envms\FluentPDO;

use Countable;
use PDOStatement;
use Traversable;

use function is_array;

class Utilities
{
    public static function toUpperWords(string $string): string
    {
        return trim(strtoupper((new Regex())->camelCaseSpaced($string)));
    }

    public static function formatQuery(string $query): string
    {
        $regex = new Regex();

        $query = $regex->splitClauses($query);
        $query = $regex->splitSubClauses($query);
        $query = $regex->removeLineEndWhitespace($query);

        return $query;
    }

    /**
     * Converts columns from strings to types according to PDOStatement::columnMeta()
     *
     * @param PDOStatement $statement
     * @param array|Traversable $rows - provided by PDOStatement::fetch with PDO::FETCH_ASSOC
     *
     * @return array|Traversable
     */
    public static function stringToNumeric(PDOStatement $statement, $rows)
    {
        for ($i = 0; ($columnMeta = $statement->getColumnMeta($i)) !== false; $i++) {
            $type = $columnMeta['native_type'];

            switch ($type) {
                case 'DECIMAL':
                case 'DOUBLE':
                case 'FLOAT':
                case 'INT24':
                case 'LONG':
                case 'LONGLONG':
                case 'NEWDECIMAL':
                case 'SHORT':
                case 'TINY':
                    if (isset($rows[$columnMeta['name']])) {
                        $rows[$columnMeta['name']] = $rows[$columnMeta['name']] + 0;
                    } else {
                        if (is_array($rows) || $rows instanceof Traversable) {
                            foreach ($rows as &$row) {
                                if (isset($row[$columnMeta['name']])) {
                                    $row[$columnMeta['name']] = $row[$columnMeta['name']] + 0;
                                }
                            }
                            unset($row);
                        }
                    }
                    break;
                default:
                    // return as string
                    break;
            }
        }

        return $rows;
    }

    /**
     * @param $value
     *
     * @return bool|array
     */
    public static function convertSqlWriteValues($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::convertValue($v);
            }
        } else {
            $value = self::convertValue($value);
        }

        return $value;
    }

    /**
     * @param $value
     *
     * @return int|string
     */
    public static function convertValue($value)
    {
        switch (gettype($value)) {
            case 'boolean':
                $conversion = ($value) ? 1 : 0;
                break;
            default:
                $conversion = $value;
                break;
        }

        return $conversion;
    }

    /**
     * @param array|Countable|mixed $subject
     *
     * @return bool
     */
    public static function isCountable($subject): bool
    {
        return (is_array($subject) || ($subject instanceof Countable));
    }

    /**
     * @param mixed $value
     *
     * @return Literal|mixed
     */
    public static function nullToLiteral($value)
    {
        if ($value === null) {
            return new Literal('NULL');
        }

        return $value;
    }
}
