<?php

namespace Joseki\Migration\DI;

use Joseki\Migration\InvalidStateException;
use Nette\Caching\Storages\MemoryStorage;
use Nette\DI\CompilerExtension;
use Nette\Loaders\RobotLoader;
use Nette\Reflection\ClassType;

class MigrationExtension extends CompilerExtension
{
    const TAG_JOSEKI_COMMAND = 'joseki.console.command';
    const TAG_KDYBY_COMMAND = 'kdyby.console.command';

    public $defaults = [
        'migrationDir' => '',
        'logFile' => '_schema.txt'
    ];



    public function loadConfiguration()
    {
        $container = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);

        if (!$config['migrationDir']) {
            throw new InvalidStateException('migrationDir parameter is required.');
        }

        if (!$config['logFile']) {
            $config['logFile'] = rtrim($config['migrationDir'], "/") . '/_schema.txt';
        }

        $container->addDefinition($this->prefix('schema'))
            ->setClass('Joseki\Migration\Console\Command\Schema', [$config['logFile'], $config['migrationDir']])
            ->addTag(self::TAG_JOSEKI_COMMAND)
            ->addTag(self::TAG_KDYBY_COMMAND);

        $counter = 0;

        foreach ($this->getFiles($config['migrationDir']) as $class) {
            if (!$class = $this->resolveRealClassName($class)) {
                continue;
            }

            $container->addDefinition(($this->prefix('migration' . ($counter++))))
                ->setClass($class)
                ->setInject(false)
                ->setAutowired(false);
        }
    }



    /**
     * @param string $class
     * @param array $config
     * @return NULL|string
     */
    protected function resolveRealClassName($class)
    {
        if (!class_exists($class)) {
            return null; // prevent meaningless exceptions
        }
        try {
            $refl = ClassType::from($class);
        } catch (\ReflectionException $e) {
            return null;
        }
        if (!$refl->isInstantiable() || !$refl->isSubclassOf('Joseki\Migration\AbstractMigration')) {
            return null; // class is not a migration
        }
        if ($this->getContainerBuilder()->findByType($class)) {
            return null; // presenter is already registered
        }
        return $refl->getName();
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