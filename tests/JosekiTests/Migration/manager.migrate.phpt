<?php

namespace JosekiTests\Migration;

use Joseki\Migration\AbstractMigration;
use Joseki\Migration\Manager;
use Mockery as m;
use Mockery\Mock;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class Migration_1443096980_Mock1 extends AbstractMigration
{
}

class Migration_1443096980_Mock2 extends AbstractMigration
{
}

class Migration_1443096984_Mock3 extends AbstractMigration
{
}

class Migration_1443096986_Mock4 extends AbstractMigration
{
}

/**
 * @testCase
 */
class ManagerMigrationTest extends \Tester\TestCase
{
    /**
     * @return \Joseki\Migration\Database\Repository|Mock
     */
    private function createRepository()
    {
        $repository = m::mock('Joseki\Migration\Database\Repository');
        return $repository;
    }



    public function testDuplicateVersion()
    {
        $migrationDir = __DIR__ . '/files';
        $migrationPrefix = 'Migration';

        $repository = $this->createRepository();

        $manager = new Manager($migrationDir, $migrationPrefix, $repository);
        $manager->add(new Migration_1443096980_Mock1());
        Assert::exception(
            function () use ($manager) {
                $manager->add(new Migration_1443096980_Mock2());
            },
            'Exception',
            '2 migrations with same version \'1443096980\' detected'
        );

        Assert::true(true);
    }



    public function testMigrateAll()
    {
        $migrationDir = __DIR__ . '/files';
        $migrationPrefix = 'Migration';

        $repository = $this->createRepository();
        $repository->shouldReceive('getCurrentVersion')->andReturnValues([0]);
        $repository->shouldReceive('getExistingVersions')->andReturnValues([[]]);
        $repository->shouldReceive('migrate');

        $manager = new Manager($migrationDir, $migrationPrefix, $repository);

        $migration1 = new Migration_1443096980_Mock1();
        $migration2 = new Migration_1443096984_Mock3();

        $manager->add($migration1);
        $manager->add($migration2);

        $manager->migrate();

        $repository->shouldHaveReceived('migrate')->twice();
        $repository->shouldHaveReceived('migrate')->with(
            m::on(
                function ($m) {
                    return $m instanceof Migration_1443096980_Mock1;
                }
            )
        );
        $repository->shouldHaveReceived('migrate')->with(
            m::on(
                function ($m) {
                    return $m instanceof Migration_1443096984_Mock3;
                }
            )
        );

        Assert::true(true);
    }



    public function testMigrateAllWithCurrent()
    {
        $migrationDir = __DIR__ . '/files';
        $migrationPrefix = 'Migration';

        $repository = $this->createRepository();
        $repository->shouldReceive('getCurrentVersion')->andReturnValues([1443096980]);
        $repository->shouldReceive('getExistingVersions')->andReturnValues([[1443096980]]);
        $repository->shouldReceive('migrate');

        $manager = new Manager($migrationDir, $migrationPrefix, $repository);

        $migration1 = new Migration_1443096980_Mock1();
        $migration2 = new Migration_1443096984_Mock3();

        $manager->add($migration1);
        $manager->add($migration2);

        $manager->migrate();

        $repository->shouldHaveReceived('migrate')->with(
            m::on(
                function ($m) {
                    return $m instanceof Migration_1443096984_Mock3;
                }
            )
        );
        $repository->shouldHaveReceived('migrate')->once();

        Assert::true(true);
    }



    public function testMigrateToDate()
    {
        $migrationDir = __DIR__ . '/files';
        $migrationPrefix = 'Migration';

        $repository = $this->createRepository();
        $repository->shouldReceive('getCurrentVersion')->andReturnValues([0]);
        $repository->shouldReceive('getExistingVersions')->andReturnValues([[]]);
        $repository->shouldReceive('migrate');

        $manager = new Manager($migrationDir, $migrationPrefix, $repository);

        $migration1 = new Migration_1443096980_Mock1();
        $migration2 = new Migration_1443096984_Mock3();

        $manager->add($migration1);
        $manager->add($migration2);

        $date = new \DateTime();
        $manager->migrateToDateTime($date->setTimestamp(1443096982));

        $repository->shouldHaveReceived('migrate')->with(
            m::on(
                function ($m) {
                    return $m instanceof Migration_1443096980_Mock1;
                }
            )
        );
        $repository->shouldHaveReceived('migrate')->once();

        Assert::true(true);
    }



    public function testSkippedMigrationDetection()
    {
        $migrationDir = __DIR__ . '/files';
        $migrationPrefix = 'Migration';

        $repository = $this->createRepository();
        $repository->shouldReceive('getCurrentVersion')->andReturnValues([1443096984]);
        $repository->shouldReceive('getExistingVersions')->andReturnValues([[1443096984]]);
        $repository->shouldReceive('migrate');

        $manager = new Manager($migrationDir, $migrationPrefix, $repository);

        $migration1 = new Migration_1443096980_Mock1();
        $migration2 = new Migration_1443096984_Mock3();
        $migration3 = new Migration_1443096986_Mock4();

        $manager->add($migration1);
        $manager->add($migration2);
        $manager->add($migration3);

        Assert::exception(
            function () use ($manager) {
                $manager->migrate();
            },
            'Joseki\Migration\InvalidStateException'
        );
    }
}

\run(new ManagerMigrationTest());
