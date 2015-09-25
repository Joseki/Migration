<?php

namespace JosekiTests\Migration;

use Joseki\Migration\Generator\MigrationClassGenerator;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class MigrationClassGeneratorTest extends \Tester\TestCase
{

    public function testGenerator()
    {
        $name = 'Test';
        $prefix = 'Migration';
        $timestamp = 1442930919;
        $class = $prefix . '_' . $timestamp . '_' . $name;

        $generator = new MigrationClassGenerator($name, $prefix, $timestamp);
        $generator->setQueries(array('SELECT 1 FROM DUAL', 'SELECT 2 FROM DUAL'));

        $file = __DIR__ . "/output/$class.php";

        @unlink($file);

        Assert::false(file_exists($file));
        $generator->saveToDirectory(__DIR__ . '/output/');
        Assert::true(file_exists($file));

        Assert::matchFile(__DIR__ . "/files/migration.class.generator.expected", $generator->__toString());
        Assert::matchFile(__DIR__ . "/files/migration.class.generator.expected", file_get_contents($file));

        unlink($file);
    }
}

\run(new MigrationClassGeneratorTest());
