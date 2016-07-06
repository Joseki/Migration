<?php

namespace JosekiTests\Migration;

use Joseki\Migration\Console\Command\Create;
use Joseki\Migration\Console\Command\Schema;
use Joseki\Migration\DI\MigrationExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\Utils\Random;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class CommandCreateTest extends \Tester\TestCase
{

    public function setUp()
    {
        @mkdir(__DIR__ . '/output/create', 0755, true);
        \Tester\Helpers::purge(__DIR__ . '/output/create');
    }

    private function prepareConfigurator()
    {
        $configurator = new Configurator;
        $configurator->setTempDirectory(TEMP_DIR);
        $configurator->addParameters(array('container' => array('class' => 'SystemContainer_' . Random::generate())));

        $configurator->onCompile[] = function ($configurator, Compiler $compiler) {
            $compiler->addExtension('Migration', new MigrationExtension());
        };
        $configurator->addConfig(__DIR__ . '/config/config.create.neon');

        return $configurator;
    }



    public function testCreateCommand()
    {
        $configurator = $this->prepareConfigurator();

        /** @var \Nette\DI\Container $container */
        $container = $configurator->createContainer();

        /** @var Schema $command */
        $command = $container->getByType('Joseki\Migration\Console\Command\Create');
        Assert::true($command instanceof Create);

        $application = new Application();
        $application->add($command);

        $command = $application->find('joseki:migration:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'name' => 'Foo']);
    }
}

\run(new CommandCreateTest());
