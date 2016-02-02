<?php

/**
 * @dataProvider? config/databases.ini
 * @testCase
 */

namespace JosekiTests\Migration;

use Joseki\Migration\Database\Repository;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../bootstrap.php';

class RepositoryAdapterTest extends \Tester\TestCase
{

    public function testAdapter()
    {
        if (!extension_loaded('sqlsrv')) {
            Environment::skip('sqlsrv not loaded');
        }

        global $config;
        $connection = new \Dibi\Connection($config);
        $repository = new Repository('foo', $connection);
        Assert::equal(get_class($repository->getAdapter()), sprintf('Joseki\Migration\Database\Adapters\%sAdapter', $config['system']));
    }

}

\run(new RepositoryAdapterTest());
