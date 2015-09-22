<?php

namespace Joseki\Migration\DI;

use Joseki\Migration\InvalidStateException;
use Nette\DI\CompilerExtension;

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
    }
}