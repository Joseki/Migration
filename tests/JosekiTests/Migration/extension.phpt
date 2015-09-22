<?php

namespace JosekiTests\Migration;

use Joseki\Migration\Console\Command\Schema;
use Joseki\Migration\DI\MigrationExtension;
use Joseki\Migration\Generator\MigrationClassGenerator;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\Reflection\ClassType;
use Nette\Utils\Random;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ExtensionTest extends \Tester\TestCase
{

    private function prepareConfigurator()
    {
        $configurator = new Configurator;
        $configurator->setTempDirectory(TEMP_DIR);
        $configurator->addParameters(array('container' => array('class' => 'SystemContainer_' . Random::generate())));

        $configurator->onCompile[] = function ($configurator, Compiler $compiler) {
            $compiler->addExtension('Migration', new MigrationExtension());
        };

        return $configurator;
    }



    public function testExtensionLoad()
    {
        $configurator = $this->prepareConfigurator();
        $configurator->addConfig(__DIR__ . '/config/config.loader.neon', $configurator::NONE);

        /** @var \Nette\DI\Container $container */
        $container = $configurator->createContainer();

        /** @var Schema $command */
        $command = $container->getByType('Joseki\Migration\Console\Command\Schema');
        Assert::true($command instanceof Schema);

        $objects = $container->findByType('Joseki\Migration\AbstractMigration');
        Assert::count(3, $objects);
    }



    public function testGenerator()
    {
        $class = 'Test';
        $namespace = 'JosekiTests\MigrationTest';
        $timestamp = 1442930919;

        $file = __DIR__ . '/output/Migration_' . $timestamp . '_' . $class . '.php';
        @unlink($file);

        $configurator = $this->prepareConfigurator();
        $configurator->addConfig(__DIR__ . '/config/config.generator.neon', $configurator::NONE);

        /** @var \Nette\DI\Container $container */
        $container = $configurator->createContainer();

        $gen = new MigrationClassGenerator($class, $namespace, $timestamp);
        $gen->setQueries(array('SELECT 1 FROM DUAL', 'SELECT 2 FROM DUAL'));
        $gen->saveToDirectory(__DIR__ . '/output/');

        Assert::true(file_exists($file));

        require $file;

        $refl = ClassType::from($namespace . '\\' . 'Migration_' . $timestamp . '_' . $class);

        Assert::true($refl->isInstantiable());
        Assert::true($refl->isSubclassOf('Joseki\Migration\DefaultMigration'));
        Assert::true($refl->isFinal());
        Assert::true($refl->hasMethod('beforeMigrate'));
        Assert::true($refl->hasMethod('migrate'));
        Assert::true($refl->hasMethod('afterMigrate'));

        unlink($file); // comment this line to see the result
    }
}

\run(new ExtensionTest());
