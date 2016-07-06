<?php

namespace Joseki\Migration\DI;

use Joseki\Utils\FileSystem;
use Nette\Caching\Storages\MemoryStorage;
use Nette\DI\CompilerExtension;
use Nette\Loaders\RobotLoader;
use Nette\Reflection\ClassType;
use Nette\Utils\Validators;

class MigrationExtension extends CompilerExtension
{
    const TAG_JOSEKI_COMMAND = 'joseki.console.command';
    const TAG_KDYBY_COMMAND = 'kdyby.console.command';

    public $defaults = [
        'migrationDir' => null,
        'migrationPrefix' => 'Migration',
        'migrationTable' => '_migration_log',
        'logFile' => null,
        'options' => [],
    ];



    public function loadConfiguration()
    {
        $container = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);

        Validators::assert($config['migrationDir'], 'string', 'Migration location directory');
        Validators::assert($config['migrationPrefix'], 'string', 'Migration name prefix');
        Validators::assert($config['options'], 'array', 'Generated SQL options');

        if (!$config['logFile']) {
            $config['logFile'] = FileSystem::normalizePath(sprintf('%s/_schema.txt', $config['migrationDir']));
        }

        $container->addDefinition($this->prefix('repository'))
            ->setClass('Joseki\Migration\Database\Repository', [$config['migrationTable']]);

        $manager = $container->addDefinition($this->prefix('manager'))
            ->setClass('Joseki\Migration\Manager', [$config['migrationDir'], $config['migrationPrefix']]);

        $container->addDefinition($this->prefix('command.schema'))
            ->setClass('Joseki\Migration\Console\Command\Schema', [$config['logFile']])
            ->addTag(self::TAG_JOSEKI_COMMAND)
            ->addTag(self::TAG_KDYBY_COMMAND);

        $container->addDefinition($this->prefix('command.create'))
            ->setClass('Joseki\Migration\Console\Command\Create')
            ->addTag(self::TAG_JOSEKI_COMMAND)
            ->addTag(self::TAG_KDYBY_COMMAND);

        $container->addDefinition($this->prefix('command.migrate'))
            ->setClass('Joseki\Migration\Console\Command\Migrate')
            ->addTag(self::TAG_JOSEKI_COMMAND)
            ->addTag(self::TAG_KDYBY_COMMAND);

        $container->addDefinition($this->prefix('platform'))
            ->setClass('Doctrine\DBAL\Platforms\MySqlPlatform');

        $container->addDefinition($this->prefix('generator.lm'))
            ->setClass('Joseki\Migration\Generator\LeanMapperSchemaGenerator', [$config['options']]);

        foreach ($this->getFiles($config['migrationDir']) as $index => $class) {
            if (!$class = $this->resolveRealClassName($class)) {
                continue;
            }

            $name = $this->prefix('migration.' . $index);

            $container->addDefinition($name)
                ->setClass($class)
                ->setInject(false)
                ->setAutowired(false);

            $manager->addSetup('add', ['@' . $name]);
        }
    }



    public function beforeCompile()
    {
        $container = $this->getContainerBuilder();

        $repositoryDefinitions = $container->findByType('LeanMapper\Repository');
        $schemaCommandDefinition = $container->getDefinition($this->prefix('command.schema'));

        foreach ($repositoryDefinitions as $serviceDefinition) {
            $class = $serviceDefinition->getClass();
            $schemaCommandDefinition->addSetup('addRepository', array(sprintf('@%s', $class)));
        }
    }



    /**
     * @param string $class
     * @return NULL|string
     */
    protected function resolveRealClassName($class)
    {
        if (!class_exists($class)) {
            return null; // prevent meaningless exceptions
        }
        try {
            $reflection = ClassType::from($class);
        } catch (\ReflectionException $e) {
            return null;
        }
        if (!$reflection->isInstantiable() || !$reflection->isSubclassOf('Joseki\Migration\AbstractMigration')) {
            return null; // class is not a migration
        }
        if ($this->getContainerBuilder()->findByType($class)) {
            return null; // migration is already registered
        }
        return $reflection->getName();
    }



    protected function getFiles($directory)
    {
        $robot = new RobotLoader();
        $robot->addDirectory($directory);
        $robot->setCacheStorage(new MemoryStorage());
        $robot->rebuild();

        $indexed = array_keys($robot->getIndexedClasses());

        return $indexed;
    }
}
