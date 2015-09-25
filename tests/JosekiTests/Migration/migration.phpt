<?php

namespace JosekiTests\Migration;

use Joseki\Migration\AbstractMigration;
use Joseki\Migration\Manager;
use Mockery\Mock;
use Tester\Assert;
use Mockery as m;

require_once __DIR__ . '/../bootstrap.php';

class Migration_1443096980_MigrationMock1 extends AbstractMigration
{

    public function getName()
    {
        return 'Mock 1';
    }
}

class Migration_1443096980_MigrationMock2 extends AbstractMigration
{

}

class Migration_1443096984_MigrationMock3 extends AbstractMigration
{

}

/**
 * @testCase
 */
class MigrationTest extends \Tester\TestCase
{

    public function testVersion()
    {
        $migration = new Migration_1443096980_MigrationMock1();
        Assert::equal(1443096980, $migration->getVersion());
        $migration = new Migration_1443096980_MigrationMock2();
        Assert::equal(1443096980, $migration->getVersion());
        $migration = new Migration_1443096984_MigrationMock3();
        Assert::equal(1443096984, $migration->getVersion());
    }



    public function testName()
    {
        $migration = new Migration_1443096980_MigrationMock1();
        Assert::equal('Mock 1', $migration->getName());
        $migration = new Migration_1443096984_MigrationMock3();
        Assert::equal('MigrationMock3', $migration->getName());
    }

}

\run(new MigrationTest());
