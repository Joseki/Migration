<?php

namespace JosekiTests\Migration;

use Joseki\Migration\Console\Command\Create;
use Joseki\Migration\Console\Command\Migrate;
use Joseki\Migration\Console\Command\Schema;
use Joseki\Migration\DI\MigrationExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
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
        $command = $container->getByType('Joseki\Migration\Console\Command\Create');
        Assert::true($command instanceof Create);
        $command = $container->getByType('Joseki\Migration\Console\Command\Migrate');
        Assert::true($command instanceof Migrate);

        $objects = $container->findByType('Joseki\Migration\AbstractMigration');
        Assert::count(3, $objects);
    }
}

\run(new ExtensionTest());
