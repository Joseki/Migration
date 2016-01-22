<?php

namespace JosekiTests\Migration;

use Joseki\Migration\Database\Adapters\MysqlAdapter;
use Joseki\Migration\Database\Adapters\SqlsrvAdapter;
use Joseki\Migration\Database\Repository;
use Joseki\Migration\DefaultMigration;
use Mockery as m;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class Migration_1443096980_Repository1 extends DefaultMigration
{
}

class Migration_1443096982_Repository2 extends DefaultMigration
{
}

/**
 * @testCase
 */
class RepositoryTest extends \Tester\TestCase
{

    public function testMigrate()
    {
        $connection = new \Dibi\Connection(['username' => 'root', 'password' => '', 'host' => '127.0.0.1', 'database' => 'testing']);

        $migration1 = new Migration_1443096980_Repository1($connection);
        $migration2 = new Migration_1443096982_Repository2($connection);

        $repository = new Repository('foo', $connection);
        Assert::equal(0, $repository->getCurrentVersion());
        $repository->migrate($migration1);
        Assert::equal(1443096980, $repository->getCurrentVersion());
        $repository->migrate($migration2);
        Assert::equal(1443096982, $repository->getCurrentVersion());

        Assert::true(true);
    }



    public function testAdapter()
    {
        $config = ['username' => 'root', 'password' => '', 'host' => '127.0.0.1', 'database' => 'testing', 'lazy' => true];

        $mysqlConnection = new \Dibi\Connection($config);
        $repository = new Repository('foo', $mysqlConnection);
        Assert::true($repository->getAdapter() instanceof MysqlAdapter);

        $config['driver'] = 'sqlsrv';
        $sqlsrvConnection = new \Dibi\Connection($config);
        $repository = new Repository('foo', $sqlsrvConnection);
        Assert::true($repository->getAdapter() instanceof SqlsrvAdapter);
    }

}

\run(new RepositoryTest());
