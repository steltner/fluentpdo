<?php declare(strict_types=1);

namespace Envms\FluentPDO;

use PDO;
use PHPUnit\Framework\TestCase;

use function getenv;

class PDOTestCase extends TestCase
{
    protected Query $fluent;

    protected function setUp(): void
    {
        if (getenv('TRAVIS')) {
            $pdo = new PDO("mysql:dbname=fluentdb;host=localhost;charset=utf8", "root");
        } else {
            $pdo = new PDO("mysql:dbname=fluentdb;host=localhost;charset=utf8", "root", "root");
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_BOTH);

        $this->fluent = new Query($pdo);
    }
}
