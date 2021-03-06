<?php declare(strict_types=1);

namespace Envms\FluentPDO;

use PHPUnit\Framework\TestCase;

class StructureTest extends TestCase
{
    public function testBasicKey()
    {
        $structure = new Structure();

        self::assertEquals('id', $structure->getPrimaryKey('user'));
        self::assertEquals('user_id', $structure->getForeignKey('user'));
    }

    public function testCustomKey()
    {
        $structure = new Structure('whatAnId', '%s_\xid');

        self::assertEquals('whatAnId', $structure->getPrimaryKey('user'));
        self::assertEquals('user_\xid', $structure->getForeignKey('user'));
    }

    public function testMethodKey()
    {
        $structure = new Structure('id', ['Envms\FluentPDO\StructureTest', 'suffix']);

        self::assertEquals('id', $structure->getPrimaryKey('user'));
        self::assertEquals('user_id', $structure->getForeignKey('user'));
    }

    /**
     * @param $table
     *
     * @return string
     */
    public static function suffix($table)
    {
        return $table . '_id';
    }

}
