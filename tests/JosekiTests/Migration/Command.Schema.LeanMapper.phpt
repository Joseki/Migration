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
class CommandSchemaLeanMapper extends \Tester\TestCase
{

    private function prepareConfigurator()
    {
        $configurator = new Configurator;
        $configurator->setTempDirectory(TEMP_DIR);
        $configurator->addParameters(array('container' => array('class' => 'SystemContainer_' . Random::generate())));

        $configurator->onCompile[] = function ($configurator, Compiler $compiler) {
            $compiler->addExtension('Migration', new MigrationExtension());
        };
        $configurator->addConfig(__DIR__ . '/config/config.schema.neon', $configurator::NONE);

        return $configurator;
    }



    public function testHasMany()
    {
        $configurator = $this->prepareConfigurator();
        $configurator->addConfig(__DIR__ . '/config/config.leanmapper.1.neon', $configurator::NONE);

        /** @var \Nette\DI\Container $container */
        $container = $configurator->createContainer();

        /** @var Schema $command */
        $command = $container->getByType('Joseki\Migration\Console\Command\Schema');
        Assert::true($command instanceof Schema);

        $application = new Application();
        $application->add($command);

        $command = $application->find('joseki:migration:from-lm');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'name' => 'Foo', '--print' => true]);

        Assert::matchFile(__DIR__ . '/files/Command.Schema.LeanMapper.1.expect', $commandTester->getDisplay());
    }



    public function testHasOne()
    {
        $configurator = $this->prepareConfigurator();
        $configurator->addConfig(__DIR__ . '/config/config.leanmapper.2.neon', $configurator::NONE);

        /** @var \Nette\DI\Container $container */
        $container = $configurator->createContainer();

        /** @var Schema $command */
        $command = $container->getByType('Joseki\Migration\Console\Command\Schema');
        Assert::true($command instanceof Schema);

        $application = new Application();
        $application->add($command);

        $command = $application->find('joseki:migration:from-lm');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'name' => 'Foo', '--print' => true]);

        Assert::matchFile(__DIR__ . '/files/Command.Schema.LeanMapper.2.expect', $commandTester->getDisplay());
    }
}

\run(new CommandSchemaLeanMapper());
