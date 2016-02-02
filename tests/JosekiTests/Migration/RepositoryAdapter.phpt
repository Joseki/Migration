<?php

/**
 * @dataProvider? config/databases.ini
 * @testCase
 */

namespace JosekiTests\Migration;

use Dibi\NotSupportedException;
use Joseki\Migration\Database\Repository;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class RepositoryAdapterTest extends \Tester\TestCase
{

    public function testAdapter()
    {
        global $config;
        try {
            $connection = new \Dibi\Connection($config);
        } catch (NotSupportedException $e) {

        }

        $repository = new Repository('foo', $connection);
        Assert::equal(get_class($repository->getAdapter()), sprintf('Joseki\Migration\Database\Adapters\%sAdapter', $config['system']));
    }

}

\run(new RepositoryAdapterTest());
